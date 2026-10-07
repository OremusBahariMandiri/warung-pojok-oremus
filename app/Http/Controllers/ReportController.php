<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Products;
use App\Models\Reports;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of sales reports.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $data = $this->reportService->getAllReports($filters);

        // Cek apakah sudah ada report hari ini
        $todayReport = Reports::whereDate('report_date', today())->first();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $data['reports'],
                'summary' => $data['summary'],
            ]);
        }

        return view('pages.reports.index', [
            'reports'      => $data['reports'],
            'summary'      => $data['summary'],
            'todayReport'  => $todayReport,
        ]);
    }

    /**
     * Show the form for creating a new sales report.
     * If a report already exists today, redirect back with warning.
     */
    public function create()
    {
        // Jika sudah ada laporan hari ini, redirect ke index dengan pesan
        $todayReport = Reports::whereDate('report_date', today())->first();
        if ($todayReport) {
            return redirect()->route('reports.index')
                ->with('today_report_exists', $todayReport->id);
        }

        $products = Products::with([
            'unit',
            'productHpps.sellingUnit',
            'restockItems' => function ($q) {
                $q->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            }
        ])
            ->select([
                'id',
                'unit_id',
                'prod_code',
                'prod_name',
                'current_stock',
                'unit_price',
            ])->orderBy('prod_name', 'asc')->get();

        $products->each(function ($p) {
            $latestRestock = $p->restockItems->first();
            $p->latest_purchase_price = $latestRestock ? (float) $latestRestock->purchase_price : (float) $p->unit_price;

            $pricesByUnit = [];
            foreach ($p->restockItems as $ri) {
                if (!isset($pricesByUnit[$ri->restock_unit_id])) {
                    $pricesByUnit[$ri->restock_unit_id] = (float) $ri->purchase_price;
                }
            }
            $p->purchase_prices_by_unit = $pricesByUnit;
            $p->is_flexible_product = $p->productHpps->contains(
                fn($h) => (bool)($h->is_flexible_product ?? false)
            );
        });

        $units = \App\Models\Unit::orderBy('unit_name', 'asc')->get();

        return view('pages.reports.create', compact('products', 'units'));
    }

    /**
     * Store a newly created sales report in storage.
     */
    public function store(StoreReportRequest $request)
    {
        $userId = Auth::id() ?? 1;
        try {
            $report = $this->reportService->createReport($request->validated(), $userId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['items' => $e->validator->errors()->first('items') ?: 'Stok produk tidak mencukupi, periksa kembali jumlah yang dimasukkan.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan penjualan berhasil dibuat dan stok produk telah diperbarui.',
                'data' => $report,
            ], 201);
        }

        return redirect()->route('reports.index')->with('success', 'Laporan penjualan berhasil disimpan.');
    }

    /**
     * Display the specified sales report detail.
     */
    public function show(Request $request, Reports $report)
    {
        $detailedReport = $this->reportService->getReportById($report->id);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $detailedReport,
            ]);
        }

        return view('pages.reports.show', compact('detailedReport'));
    }

    /**
     * Show the form for editing an existing sales report.
     */
    public function edit(Reports $report)
    {
        $report->load('details.product', 'details.sellingUnit');

        $products = Products::with([
            'unit',
            'productHpps.sellingUnit',
            'restockItems' => function ($q) {
                $q->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            }
        ])
            ->select([
                'id',
                'unit_id',
                'prod_code',
                'prod_name',
                'current_stock',
                'unit_price'
            ])->orderBy('prod_name', 'asc')->get();

        $originalQtyMap = $report->details->pluck('quantity', 'product_id')->toArray();
        $products->each(function ($p) use ($originalQtyMap) {
            $p->original_qty = $originalQtyMap[$p->id] ?? 0;
            $latestRestock = $p->restockItems->first();
            $p->latest_purchase_price = $latestRestock ? (float) $latestRestock->purchase_price : (float) $p->unit_price;

            $pricesByUnit = [];
            foreach ($p->restockItems as $ri) {
                if (!isset($pricesByUnit[$ri->restock_unit_id])) {
                    $pricesByUnit[$ri->restock_unit_id] = (float) $ri->purchase_price;
                }
            }
            $p->purchase_prices_by_unit = $pricesByUnit;
            $p->is_flexible_product = $p->productHpps->contains(
                fn($h) => (bool)($h->is_flexible_product ?? false)
            );
        });

        $units = \App\Models\Unit::orderBy('unit_name', 'asc')->get();

        return view('pages.reports.edit', compact('report', 'products', 'units'));
    }

    /**
     * Update an existing sales report (recalculate stock and totals).
     */
    public function update(UpdateReportRequest $request, Reports $report)
    {
        $userId = Auth::id() ?? 1;
        try {
            $updatedReport = $this->reportService->updateReport($report, $request->validated(), $userId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['items' => $e->validator->errors()->first('items') ?: 'Stok produk tidak mencukupi, periksa kembali jumlah yang dimasukkan.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Laporan penjualan berhasil diperbarui.',
                'data'    => $updatedReport,
            ]);
        }

        return redirect()->route('reports.index')->with('success', 'Laporan penjualan berhasil diperbarui.');
    }

    /**
     * Display multi-day / period summary recap.
     */
    public function summary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $recap = $this->reportService->getSummaryByPeriod($startDate, $endDate);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $recap,
            ]);
        }

        return view('pages.reports.summary', compact('recap'));
    }

    /**
     * Remove / Rollback the specified sales report.
     */
    public function destroy(Request $request, Reports $report)
    {
        $this->reportService->deleteReport($report);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan penjualan berhasil dihapus dan stok produk telah dikembalikan.',
            ]);
        }

        return redirect()->route('reports.index')->with('success', 'Laporan penjualan berhasil dihapus.');
    }
    /**
     * Display the salary calculator page based on report margin.
     */
    public function salaryCalculator(Request $request)
    {
        // Jika ada tanggal dari query param (dari tombol kalkulator di index),
        // simpan ke session lalu redirect ke URL bersih tanpa params
        if ($request->has('start_date') || $request->has('end_date')) {
            $request->session()->put('salary_calc_start', $request->input('start_date', today()->toDateString()));
            $request->session()->put('salary_calc_end',   $request->input('end_date',   today()->toDateString()));
            if ($request->has('report_id')) {
                $request->session()->put('salary_calc_report_id', $request->input('report_id'));
            } else {
                $request->session()->forget('salary_calc_report_id');
            }
            return redirect()->route('reports.salary_calculator');
        }

        // Ambil report_id dari query param atau session
        $reportId = $request->input('report_id') ?? $request->session()->get('salary_calc_report_id');
        $reportId = $reportId ? (int) $reportId : null;

        // Jika ada report_id, validasi dan ambil tanggal dari report tersebut
        if ($reportId) {
            $reportModel = Reports::find($reportId);
            if ($reportModel) {
                $startDate = \Carbon\Carbon::parse($reportModel->report_date)->toDateString();
                $endDate   = $startDate;
            } else {
                // report_id tidak valid — clear dari session dan fallback ke tanggal session
                $request->session()->forget('salary_calc_report_id');
                $reportId  = null;
                $startDate = $request->session()->get('salary_calc_start', today()->toDateString());
                $endDate   = $request->session()->get('salary_calc_end',   today()->toDateString());
            }
        } else {
            $startDate = $request->session()->get('salary_calc_start', today()->toDateString());
            $endDate   = $request->session()->get('salary_calc_end',   today()->toDateString());
        }

        $summary  = $this->reportService->getSummaryForCalculator($startDate, $endDate, $reportId);
        $reportId = $reportId ?? '';   // pastikan tidak null saat di-pass ke Blade/JS

        return view('pages.reports.salary_calculator', compact('summary', 'startDate', 'endDate', 'reportId'));
    }

    /**
     * Simpan hasil kalkulasi gaji ke kolom komisi pada laporan di rentang tanggal.
     */
    public function storeSalaryCalculation(Request $request)
    {
        $validated = $request->validate([
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'commission_type'     => 'required|in:persentase,nominal',
            'commission_value'    => 'required|numeric',
            'profit_share_amount' => 'required|numeric',
            'owner_share_amount'  => 'required|numeric',
            'notes'               => 'nullable|string|max:500',
            'report_id'           => 'nullable|integer|exists:reports,id',
        ]);

        // Pastikan key selalu ada (nullable field tidak selalu di-include Laravel validator)
        $validated['report_id'] = $validated['report_id'] ?? null;
        $validated['notes']     = $validated['notes'] ?? null;

        $updated = $this->reportService->storeSalaryCalculation($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data komisi berhasil disimpan.',
            'updated' => $updated,
        ]);
    }

    public function checkDate(Request $request): \Illuminate\Http\JsonResponse
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['exists' => false, 'report_id' => null]);
        }

        // Parse tanggal — toleransi format YYYY-MM-DD atau YYYY-MM-DDTHH:mm
        try {
            $parsedDate = \Carbon\Carbon::parse($date)->startOfDay();
        } catch (\Exception $e) {
            return response()->json(['exists' => false, 'report_id' => null]);
        }

        // Cek di tabel reports kolom report_date (date only, abaikan jam)
        $existing = Reports::whereDate('report_date', $parsedDate->toDateString())
            ->first();

        return response()->json([
            'exists'    => $existing !== null,
            'report_id' => $existing?->id,
        ]);
    }
}