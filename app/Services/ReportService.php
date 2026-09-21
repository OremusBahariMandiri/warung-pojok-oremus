<?php

namespace App\Services;

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
        $query = Reports::with(['details.product'])->orderBy('report_date', 'desc');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('report_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        $reports = $query->get();

        $overallQuantity = $reports->sum('total_quantity');
        $overallSales = (float)$reports->sum('total_sales');
        $overallHpp = (float)$reports->sum('total_hpp');
        $overallMargin = (float)$reports->sum('total_margin');

        return [
            'reports' => $reports,
            'summary' => [
                'total_quantity' => $overallQuantity,
                'total_sales' => $overallSales,
                'total_hpp' => $overallHpp,
                'total_margin' => $overallMargin,
            ]
        ];
    }

    /**
     * Get single report by ID with detail items and product relations.
     */
    public function getReportById(int $id): Reports
    {
        return Reports::with(['details.product.productHpps.hpp'])->findOrFail($id);
    }

    /**
     * Get aggregated multi-day summary for date range (as in Excel Total Margin tab).
     */
    public function getSummaryByPeriod(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $reports = Reports::with(['details.product'])
            ->whereBetween('report_date', [$start, $end])
            ->orderBy('report_date', 'asc')
            ->get();

        $dailyRecap = [];
        $overallQuantity = 0;
        $overallSales = 0.0;
        $overallHpp = 0.0;
        $overallMargin = 0.0;

        foreach ($reports as $report) {
            $dateStr = Carbon::parse($report->report_date)->format('Y-m-d');
            
            if (!isset($dailyRecap[$dateStr])) {
                $dailyRecap[$dateStr] = [
                    'date' => $dateStr,
                    'total_quantity' => 0,
                    'total_sales' => 0.0,
                    'total_hpp' => 0.0,
                    'total_margin' => 0.0,
                ];
            }

            $dailyRecap[$dateStr]['total_quantity'] += $report->total_quantity;
            $dailyRecap[$dateStr]['total_sales'] += (float)$report->total_sales;
            $dailyRecap[$dateStr]['total_hpp'] += (float)$report->total_hpp;
            $dailyRecap[$dateStr]['total_margin'] += (float)$report->total_margin;

            $overallQuantity += $report->total_quantity;
            $overallSales += (float)$report->total_sales;
            $overallHpp += (float)$report->total_hpp;
            $overallMargin += (float)$report->total_margin;
        }

        return [
            'period' => [
                'start_date' => $start->toIso8601String(),
                'end_date' => $end->toIso8601String(),
            ],
            'daily_recap' => array_values($dailyRecap),
            'overall' => [
                'total_quantity' => $overallQuantity,
                'total_sales' => $overallSales,
                'total_hpp' => $overallHpp,
                'total_margin' => $overallMargin,
            ]
        ];
    }

    /**
     * Create a sales report, deduct product stock, and log activity.
     */
    public function createReport(array $data, ?int $userId = null): Reports
    {
        return DB::transaction(function () use ($data, $userId) {
            $reportDate = !empty($data['report_date']) ? Carbon::parse($data['report_date']) : Carbon::now();

            $report = Reports::create([
                'created_by' => auth()->guard()->id(),
                'total_quantity' => 0,
                'total_sales' => 0.0,
                'total_hpp' => 0.0,
                'total_margin' => 0.0,
                'report_date' => $reportDate,
                'notes' => isset($data['notes']) ? $data['notes'] : null,
            ]);

            $headerQuantity = 0;
            $headerSales = 0.0;
            $headerHpp = 0.0;
            $headerMargin = 0.0;
            $detailsSummary = [];

            foreach ($data['items'] as $itemData) {
                $productId = $itemData['product_id'];
                $quantity = (int)$itemData['quantity'];

                // Lock product for update to safely handle stock deduction
                $product = Products::lockForUpdate()->findOrFail($productId);
                $oldStock = $product->current_stock;
                $newStock = max(0, $oldStock - $quantity);

                // Use provided price/hpp or snapshot from product
                $sellingPrice = isset($itemData['selling_price']) && $itemData['selling_price'] !== ''
                    ? (float)$itemData['selling_price']
                    : (float)$product->selling_price;

                $hpp = isset($itemData['hpp']) && $itemData['hpp'] !== ''
                    ? (float)$itemData['hpp']
                    : (float)$product->current_hpp;

                $totalPrice = $quantity * $sellingPrice;
                $totalHpp = $quantity * $hpp;
                $margin = $totalPrice - $totalHpp;

                // Update product stock
                $product->current_stock = $newStock;
                $product->save();

                // Save report detail
                ReportDetails::create([
                    'report_id' => $report->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'selling_price' => $sellingPrice,
                    'hpp' => $hpp,
                    'total_price' => $totalPrice,
                    'total_hpp' => $totalHpp,
                    'margin' => $margin,
                ]);

                $headerQuantity += $quantity;
                $headerSales += $totalPrice;
                $headerHpp += $totalHpp;
                $headerMargin += $margin;

                $detailsSummary[] = [
                    'product_id' => $productId,
                    'product_name' => $product->prod_name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'selling_price' => $sellingPrice,
                    'hpp' => $hpp,
                    'total_price' => $totalPrice,
                    'total_hpp' => $totalHpp,
                    'margin' => $margin,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                ];
            }

            // Update report totals
            $report->update([
                'total_quantity' => $headerQuantity,
                'total_sales' => $headerSales,
                'total_hpp' => $headerHpp,
                'total_margin' => $headerMargin,
            ]);

            // Log activity
            ActivityLogService::log(
                action: 'CREATE',
                module: 'REPORT',
                entityType: Reports::class,
                entityId: $report->id,
                description: "Created sales report for " . $reportDate->format('Y-m-d') . " (Sales: Rp " . number_format($headerSales, 0, ',', '.') . ", Margin: Rp " . number_format($headerMargin, 0, ',', '.') . ")",
                oldValues: null,
                newValues: [
                    'report' => $report->fresh()->toArray(),
                    'items' => $detailsSummary,
                ],
                userId: $userId
            );

            return $report->fresh()->load(['details.product']);
        });
    }

    /**
     * Delete / Cancel a sales report and rollback product stock.
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
                        'product_id' => $product->id,
                        'product_name' => $product->prod_name,
                        'restored_quantity' => $detail->quantity,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
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
                description: "Deleted & rolled back sales report of ID {$id} (" . Carbon::parse($reportDate)->format('Y-m-d') . ")",
                oldValues: [
                    'report' => $oldValues,
                    'stock_rollback' => $rollbackSummary,
                ],
                newValues: null
            );

            return $deleted;
        });
    }
}