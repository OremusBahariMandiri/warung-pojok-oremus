<?php

namespace App\Services;

use App\Models\ProductHpp;
use App\Models\Products;
use App\Models\ReportDetails;
use App\Models\Reports;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get all reports with filters and computed overall summary.
     */
    public function getAllReports(array $filters = [])
    {
        $query = Reports::with(['creator', 'details.product', 'details.sellingUnit'])->orderBy('report_date', 'desc');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('report_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        $reports = $query->get();

        $overallQuantity = $reports->sum('total_quantity');
        $overallSales    = (float) $reports->sum('total_sales');
        $overallHpp      = (float) $reports->sum('total_hpp');
        $overallMargin   = (float) $reports->sum('total_gross_margin');

        return [
            'reports' => $reports,
            'summary' => [
                'total_quantity'      => $overallQuantity,
                'total_sales'         => $overallSales,
                'total_hpp'           => $overallHpp,
                'total_gross_margin'  => $overallMargin,
            ]
        ];
    }

    /**
     * Get single report by ID with detail items and relations.
     */
    public function getReportById(int $id): Reports
    {
        return Reports::with(['creator', 'details.product.productHpps.hpps', 'details.sellingUnit'])->findOrFail($id);
    }

    /**
     * Get aggregated multi-day summary for date range.
     */
    public function getSummaryByPeriod(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $reports = Reports::with(['details.product', 'details.sellingUnit'])
            ->whereBetween('report_date', [$start, $end])
            ->orderBy('report_date', 'asc')
            ->get();

        $dailyRecap = [];
        $overallQuantity = 0;
        $overallSales    = 0.0;
        $overallHpp      = 0.0;
        $overallMargin   = 0.0;

        foreach ($reports as $report) {
            $dateStr = Carbon::parse($report->report_date)->format('Y-m-d');

            if (!isset($dailyRecap[$dateStr])) {
                $dailyRecap[$dateStr] = [
                    'date'                => $dateStr,
                    'total_quantity'      => 0,
                    'total_sales'         => 0.0,
                    'total_hpp'           => 0.0,
                    'total_gross_margin'  => 0.0,
                ];
            }

            $dailyRecap[$dateStr]['total_quantity']     += $report->total_quantity;
            $dailyRecap[$dateStr]['total_sales']        += (float) $report->total_sales;
            $dailyRecap[$dateStr]['total_hpp']          += (float) $report->total_hpp;
            $dailyRecap[$dateStr]['total_gross_margin'] += (float) $report->total_gross_margin;

            $overallQuantity += $report->total_quantity;
            $overallSales    += (float) $report->total_sales;
            $overallHpp      += (float) $report->total_hpp;
            $overallMargin   += (float) $report->total_gross_margin;
        }

        return [
            'period' => [
                'start_date' => $start->toIso8601String(),
                'end_date'   => $end->toIso8601String(),
            ],
            'daily_recap' => array_values($dailyRecap),
            'overall'     => [
                'total_quantity'     => $overallQuantity,
                'total_sales'        => $overallSales,
                'total_hpp'          => $overallHpp,
                'total_gross_margin' => $overallMargin,
            ]
        ];
    }

    /**
     * Create a sales report, snapshot pricing & HPP, deduct product stock, and log activity.
     */
    public function createReport(array $data, ?int $userId = null): Reports
    {
        return DB::transaction(function () use ($data, $userId) {
            $reportDate = !empty($data['report_date']) ? Carbon::parse($data['report_date']) : Carbon::now();
            $creatorId  = $userId ?? (auth()->guard()->check() ? auth()->guard()->id() : 1);

            $report = Reports::create([
                'created_by'         => $creatorId,
                'total_quantity'     => 0,
                'total_sales'        => 0.0,
                'total_hpp'          => 0.0,
                'total_gross_margin' => 0.0,
                'report_date'        => $reportDate,
                'notes'              => $data['notes'] ?? null,
            ]);

            $headerQuantity = 0;
            $headerSales    = 0.0;
            $headerHpp      = 0.0;
            $headerMargin   = 0.0;
            $detailsSummary = [];

            foreach ($data['items'] as $itemData) {
                $productId     = (int) $itemData['product_id'];
                $sellingUnitId = (int) $itemData['selling_unit_id'];
                $quantity      = (int) $itemData['quantity'];

                // Lock product to safely update current_stock
                $product  = Products::lockForUpdate()->findOrFail($productId);
                $oldStock = $product->current_stock;

                // $stockFinal = isset($itemData['stock_final']) && $itemData['stock_final'] !== ''
                //     ? (int) $itemData['stock_final']
                //     : max(0, $oldStock - $quantity);
                $stockFinal = $oldStock - $quantity;

                // Look up selling_price and current_hpp from ProductHpp configuration
                $config = ProductHpp::where('product_id', $productId)
                    ->where('selling_unit_id', $sellingUnitId)
                    ->first();

                $isFlexible = (bool) ($config ? ($config->is_flexible_product ?? false) : false);

                if ($isFlexible) {
                    $subtotalPrice  = isset($itemData['total_sales_manual']) ? (float) $itemData['total_sales_manual'] : 0.0;
                    $subtotalHpp    = isset($itemData['total_hpp_manual'])   ? (float) $itemData['total_hpp_manual']   : 0.0;
                    $subtotalMargin = $subtotalPrice - $subtotalHpp;
                    $sellingPrice = isset($itemData['selling_price']) && $itemData['selling_price'] !== '' && (float)$itemData['selling_price'] > 0
                        ? (float) $itemData['selling_price']
                        : ($config ? (float) $config->selling_price : 0.0);

                    $hppUnit = isset($itemData['hpp_unit']) && $itemData['hpp_unit'] !== '' && (float)$itemData['hpp_unit'] > 0
                        ? (float) $itemData['hpp_unit']
                        : ($config ? (float) $config->current_hpp : 0.0);
                    $stockFinal     = $oldStock - $quantity;

                    $product->current_stock = $stockFinal;
                    $product->save();
                } else {
                    $sellingPrice = isset($itemData['selling_price']) && $itemData['selling_price'] !== '' && (float)$itemData['selling_price'] > 0
                        ? (float) $itemData['selling_price']
                        : ($config ? (float) $config->selling_price : 0.0);

                    $hppUnit = isset($itemData['hpp_unit']) && $itemData['hpp_unit'] !== '' && (float)$itemData['hpp_unit'] > 0
                        ? (float) $itemData['hpp_unit']
                        : ($config ? (float) $config->current_hpp : 0.0);

                    $subtotalPrice  = $quantity * $sellingPrice;
                    $subtotalHpp    = $quantity * $hppUnit;
                    $subtotalMargin = $subtotalPrice - $subtotalHpp;

                    $product->current_stock = $stockFinal;
                    $product->save();
                }

                ReportDetails::create([
                    'report_id'       => $report->id,
                    'product_id'      => $productId,
                    'selling_unit_id' => $sellingUnitId,
                    'quantity'        => $quantity,
                    'stock_final'     => $stockFinal,
                    'selling_price'   => $sellingPrice,
                    'hpp_unit'        => $hppUnit,
                    'subtotal_price'  => $subtotalPrice,
                    'subtotal_hpp'    => $subtotalHpp,
                    'subtotal_margin' => $subtotalMargin,
                ]);

                $headerQuantity += $quantity;
                $headerSales    += $subtotalPrice;
                $headerHpp      += $subtotalHpp;
                $headerMargin   += $subtotalMargin;

                $detailsSummary[] = [
                    'product_id'      => $productId,
                    'product_name'    => $product->prod_name,
                    'selling_unit_id' => $sellingUnitId,
                    'quantity'        => $quantity,
                    'stock_final'     => $stockFinal,
                    'selling_price'   => $sellingPrice,
                    'hpp_unit'        => $hppUnit,
                    'subtotal_price'  => $subtotalPrice,
                    'subtotal_hpp'    => $subtotalHpp,
                    'subtotal_margin' => $subtotalMargin,
                    'old_stock'       => $oldStock,
                    'new_stock'       => $stockFinal,
                ];
            }

            // Update report totals
            $report->update([
                'total_quantity'     => $headerQuantity,
                'total_sales'        => $headerSales,
                'total_hpp'          => $headerHpp,
                'total_gross_margin' => $headerMargin,
            ]);

            ActivityLogService::log(
                action: 'CREATE',
                module: 'REPORT',
                entityType: Reports::class,
                entityId: $report->id,
                description: "Created sales report for " . $reportDate->format('Y-m-d') . " (Sales: Rp " . number_format($headerSales, 0, ',', '.') . ", Margin: Rp " . number_format($headerMargin, 0, ',', '.') . ")",
                oldValues: null,
                newValues: [
                    'report' => $report->fresh()->toArray(),
                    'items'  => $detailsSummary,
                ],
                userId: $creatorId
            );

            return $report->fresh()->load(['details.product', 'details.sellingUnit']);
        });
    }

    /**
     * Update an existing sales report: rollback old stock, apply new items, recalculate totals.
     */
    public function updateReport(Reports $report, array $data, ?int $userId = null): Reports
    {
        return DB::transaction(function () use ($report, $data, $userId) {
            $creatorId = $userId ?? (auth()->guard()->check() ? auth()->guard()->id() : 1);

            // 1. Rollback stok dari detail lama â€" kembalikan stok yang sudah dikurangi
            $report->load('details.product');
            foreach ($report->details as $oldDetail) {
                $product = Products::lockForUpdate()->find($oldDetail->product_id);
                if ($product) {
                    $detailConfig = \App\Models\ProductHpp::where('product_id', $oldDetail->product_id)
                        ->where('selling_unit_id', $oldDetail->selling_unit_id)
                        ->first();
                    $product->current_stock = $product->current_stock + $oldDetail->quantity;
                    $product->save();
                }
            }

            // 2. Hapus semua detail lama
            $report->details()->delete();

            // 3. Terapkan item baru
            $headerQuantity = 0;
            $headerSales    = 0.0;
            $headerHpp      = 0.0;
            $headerMargin   = 0.0;
            $detailsSummary = [];

            foreach ($data['items'] as $itemData) {
                $productId     = (int) $itemData['product_id'];
                $sellingUnitId = (int) $itemData['selling_unit_id'];
                $quantity      = (int) $itemData['quantity'];

                $product  = Products::lockForUpdate()->findOrFail($productId);
                $oldStock = $product->current_stock; // sudah di-rollback, nilainya benar (stok sebelum laporan ini)

                // FIX: selalu hitung stock_final dari oldStock (setelah rollback) dikurangi quantity baru.
                // Tidak percaya nilai form karena stock_final di form bisa stale (nilai lama sebelum user mengubah qty).
                $stockFinal = $oldStock - $quantity;

                $config = \App\Models\ProductHpp::where('product_id', $productId)
                    ->where('selling_unit_id', $sellingUnitId)
                    ->first();

                $isFlexible = (bool) ($config ? ($config->is_flexible_product ?? false) : false);

                if ($isFlexible) {
                    $subtotalPrice  = isset($itemData['total_sales_manual']) ? (float) $itemData['total_sales_manual'] : 0.0;
                    $subtotalHpp    = isset($itemData['total_hpp_manual'])   ? (float) $itemData['total_hpp_manual']   : 0.0;
                    $subtotalMargin = $subtotalPrice - $subtotalHpp;
                    $sellingPrice = isset($itemData['selling_price']) && $itemData['selling_price'] !== '' && (float)$itemData['selling_price'] > 0
                        ? (float) $itemData['selling_price']
                        : ($config ? (float) $config->selling_price : 0.0);

                    $hppUnit = isset($itemData['hpp_unit']) && $itemData['hpp_unit'] !== '' && (float)$itemData['hpp_unit'] > 0
                        ? (float) $itemData['hpp_unit']
                        : ($config ? (float) $config->current_hpp : 0.0);
                    $stockFinal     = $oldStock - $quantity;

                    $product->current_stock = $stockFinal;
                    $product->save();
                } else {
                    $sellingPrice = isset($itemData['selling_price']) && $itemData['selling_price'] !== '' && (float)$itemData['selling_price'] > 0
                        ? (float) $itemData['selling_price']
                        : ($config ? (float) $config->selling_price : 0.0);

                    $hppUnit = isset($itemData['hpp_unit']) && $itemData['hpp_unit'] !== '' && (float)$itemData['hpp_unit'] > 0
                        ? (float) $itemData['hpp_unit']
                        : ($config ? (float) $config->current_hpp : 0.0);

                    $subtotalPrice  = $quantity * $sellingPrice;
                    $subtotalHpp    = $quantity * $hppUnit;
                    $subtotalMargin = $subtotalPrice - $subtotalHpp;

                    $product->current_stock = $stockFinal;
                    $product->save();
                }

                ReportDetails::create([
                    'report_id'       => $report->id,
                    'product_id'      => $productId,
                    'selling_unit_id' => $sellingUnitId,
                    'quantity'        => $quantity,
                    'stock_final'     => $stockFinal,
                    'selling_price'   => $sellingPrice,
                    'hpp_unit'        => $hppUnit,
                    'subtotal_price'  => $subtotalPrice,
                    'subtotal_hpp'    => $subtotalHpp,
                    'subtotal_margin' => $subtotalMargin,
                ]);

                $headerQuantity += $quantity;
                $headerSales    += $subtotalPrice;
                $headerHpp      += $subtotalHpp;
                $headerMargin   += $subtotalMargin;

                $detailsSummary[] = [
                    'product_id'      => $productId,
                    'product_name'    => $product->prod_name,
                    'selling_unit_id' => $sellingUnitId,
                    'quantity'        => $quantity,
                    'stock_final'     => $stockFinal,
                    'selling_price'   => $sellingPrice,
                    'hpp_unit'        => $hppUnit,
                    'subtotal_price'  => $subtotalPrice,
                    'subtotal_hpp'    => $subtotalHpp,
                    'subtotal_margin' => $subtotalMargin,
                    'old_stock'       => $oldStock,
                    'new_stock'       => $stockFinal,
                ];
            }

            // 4. Update notes dan totals di header report
            $report->update([
                'notes'              => $data['notes'] ?? $report->notes,
                'total_quantity'     => $headerQuantity,
                'total_sales'        => $headerSales,
                'total_hpp'          => $headerHpp,
                'total_gross_margin' => $headerMargin,
            ]);

            ActivityLogService::log(
                action: 'UPDATE',
                module: 'REPORT',
                entityType: Reports::class,
                entityId: $report->id,
                description: "Updated sales report ID {$report->id} (Sales: Rp " . number_format($headerSales, 0, ',', '.') . ", Margin: Rp " . number_format($headerMargin, 0, ',', '.') . ")",
                oldValues: null,
                newValues: [
                    'report' => $report->fresh()->toArray(),
                    'items'  => $detailsSummary,
                ],
                userId: $creatorId
            );

            return $report->fresh()->load(['details.product', 'details.sellingUnit']);
        });
    }

    /**
     * Delete / Cancel a sales report and restore product stock.
     */
    public function deleteReport(Reports $report): bool
    {
        return DB::transaction(function () use ($report) {
            $report->load('details.product');
            $oldValues = $report->toArray();
            $rollbackSummary = [];

            foreach ($report->details as $detail) {
                $product = Products::lockForUpdate()->find($detail->product_id);
                if ($product) {
                    $oldStock = $product->current_stock;
                    $newStock = $oldStock + $detail->quantity;
                    $product->current_stock = $newStock;
                    $product->save();

                    $rollbackSummary[] = [
                        'product_id'        => $product->id,
                        'product_name'      => $product->prod_name,
                        'restored_quantity' => $detail->quantity,
                        'old_stock'         => $oldStock,
                        'new_stock'         => $newStock,
                    ];
                }
            }

            $id = $report->id;
            $reportDate = $report->report_date;

            $deleted = $report->delete();

            ActivityLogService::log(
                action: 'DELETE',
                module: 'REPORT',
                entityType: Reports::class,
                entityId: $id,
                description: "Deleted & rolled back sales report ID {$id} (" . Carbon::parse($reportDate)->format('Y-m-d') . ")",
                oldValues: [
                    'report'         => $oldValues,
                    'stock_rollback' => $rollbackSummary,
                ],
                newValues: null
            );

            return $deleted;
        });
    }
    /**
     * Simpan hasil kalkulasi gaji ke kolom komisi pada semua laporan di rentang tanggal.
     * Mengembalikan jumlah baris yang diperbarui.
     */
    public function storeSalaryCalculation(array $data): int
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end   = Carbon::parse($data['end_date'])->endOfDay();

        $isPersentase = $data['commission_type'] === 'persentase';

        // Cek kolom mana yang tersedia di tabel reports
        $columns = DB::select("SHOW COLUMNS FROM `reports`");
        $columnNames = array_map(fn($c) => $c->Field, $columns);

        $updateData = [
            'commission_value'    => (float) $data['commission_value'],
            'profit_share_amount' => (float) $data['profit_share_amount'],
            'owner_share_amount'  => (float) $data['owner_share_amount'],
            'updated_at'          => now(),
        ];

        if (in_array('total_net_margin', $columnNames)) {
            $updateData['total_net_margin'] = (float) $data['owner_share_amount'];
        }

        // Tentukan kolom tipe komisi berdasarkan schema yang ada
        if (in_array('comission_type_presentance', $columnNames)) {
            // Schema baru: dua kolom numeric
            $updateData['comission_type_presentance'] = $isPersentase ? (float) $data['commission_value'] : null;
            $updateData['comission_type_nominal']     = $isPersentase ? null : (float) $data['commission_value'];
        } elseif (in_array('commission_type_presentance', $columnNames)) {
            // Variasi ejaan dengan double 's'
            $updateData['commission_type_presentance'] = $isPersentase ? (float) $data['commission_value'] : null;
            $updateData['commission_type_nominal']     = $isPersentase ? null : (float) $data['commission_value'];
        } elseif (in_array('commission_type', $columnNames)) {
            // Schema lama: satu kolom string
            $updateData['commission_type'] = $data['commission_type'];
        }

        $query = DB::table('reports')->whereBetween('report_date', [$start, $end]);

        if (!empty($data['report_id'])) {
            $query->where('id', (int) $data['report_id']);
        }

        return $query->update($updateData);
    }

    /**
     * Get aggregated summary of reports for the salary calculator.
     */
    public function getSummaryForCalculator(string $startDate, string $endDate, ?int $reportId = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $reports = Reports::whereBetween('report_date', [$start, $end])->get();

        $totalReports  = $reports->count();
        $totalQuantity = (int) $reports->sum('total_quantity');
        $totalSales    = (float) $reports->sum('total_sales');
        $totalHpp      = (float) $reports->sum('total_hpp');
        $totalMargin   = (float) $reports->sum('total_gross_margin');

        // Ambil nilai komisi tersimpan
        $savedReport = null;
        if ($reportId) {
            $savedReport = $reports->firstWhere('id', $reportId);
        }
        if (!$savedReport && $reports->isNotEmpty()) {
            foreach ($reports as $r) {
                if ($r->commission_value !== null) {
                    $savedReport = $r;
                    break;
                }
            }
        }

        $savedCommissionType  = null;
        $savedCommissionValue = null;
        $savedNotes           = null;

        if ($savedReport && $savedReport->commission_value !== null) {
            // Deteksi nama kolom tipe komisi yang benar via SHOW COLUMNS
            // karena nama kolom bisa berbeda antar environment
            $columns     = DB::select("SHOW COLUMNS FROM `reports`");
            $columnNames = array_map(fn($c) => $c->Field, $columns);

            // Baca row mentah via DB::table supaya tidak bergantung pada $fillable / $casts model
            $rawRow = DB::table('reports')->where('id', $savedReport->id)->first();

            if ($rawRow) {
                $commissionValue = (float) ($rawRow->commission_value ?? 0);

                if ($commissionValue > 0) {
                    // Cek kolom presentance (persentase)
                    $presentanceCol = null;
                    if (in_array('comission_type_presentance', $columnNames)) {
                        $presentanceCol = 'comission_type_presentance';
                    } elseif (in_array('commission_type_presentance', $columnNames)) {
                        $presentanceCol = 'commission_type_presentance';
                    }

                    // Cek kolom nominal
                    $nominalCol = null;
                    if (in_array('comission_type_nominal', $columnNames)) {
                        $nominalCol = 'comission_type_nominal';
                    } elseif (in_array('commission_type_nominal', $columnNames)) {
                        $nominalCol = 'commission_type_nominal';
                    }

                    // Cek kolom tipe string lama
                    $typeStringCol = null;
                    if (in_array('commission_type', $columnNames)) {
                        $typeStringCol = 'commission_type';
                    }

                    if ($presentanceCol && !is_null($rawRow->$presentanceCol) && (float)$rawRow->$presentanceCol > 0) {
                        $savedCommissionType  = 'persentase';
                        $savedCommissionValue = (float) $rawRow->$presentanceCol;
                    } elseif ($nominalCol && !is_null($rawRow->$nominalCol) && (float)$rawRow->$nominalCol > 0) {
                        $savedCommissionType  = 'nominal';
                        $savedCommissionValue = (float) $rawRow->$nominalCol;
                    } elseif ($typeStringCol && !empty($rawRow->$typeStringCol)) {
                        // Schema lama: satu kolom string tipe + commission_value sebagai nilai
                        $savedCommissionType  = $rawRow->$typeStringCol; // 'persentase' atau 'nominal'
                        $savedCommissionValue = $commissionValue;
                    }

                    $savedNotes = $rawRow->notes ?? null;
                }
            }
        }

        return [
            'total_reports'          => $totalReports,
            'total_quantity'         => $totalQuantity,
            'total_sales'            => $totalSales,
            'total_hpp'              => $totalHpp,
            'total_gross_margin'     => $totalMargin,
            'start_date'             => $startDate,
            'end_date'               => $endDate,
            'saved_commission_type'  => $savedCommissionType,
            'saved_commission_value' => $savedCommissionValue,
            'saved_notes'            => $savedNotes,
        ];
    }
}