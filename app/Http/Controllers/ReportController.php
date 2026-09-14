<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
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

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $data['reports'],
                'summary' => $data['summary'],
            ]);
        }

        return view('pages.reports.index', [
            'reports' => $data['reports'],
            'summary' => $data['summary'],
        ]);
    }

    /**
     * Show the form for creating a new sales report.
     */
    public function create()
    {
        $products = Products::select([
            'id', 'prod_name', 'sku', 'satuan', 'selling_price', 'current_hpp', 'current_stock'
        ])->orderBy('prod_name', 'asc')->get();

        return view('pages.reports.create', compact('products'));
    }

    /**
     * Store a newly created sales report in storage.
     */
    public function store(StoreReportRequest $request)
    {
        $userId = Auth::id() ?? 1;
        $report = $this->reportService->createReport($request->validated(), $userId);

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
}
