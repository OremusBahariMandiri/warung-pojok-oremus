<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Http\Requests\UpdateStockOpnameRequest;
use App\Models\Products;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    protected StockOpnameService $opnameService;

    public function __construct(StockOpnameService $opnameService)
    {
        $this->opnameService = $opnameService;
    }

    /**
     * Display a listing of stock opname transactions.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status_opname', 'start_date', 'end_date']);
        $opnames = $this->opnameService->getAllStockOpnames($filters);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $opnames,
            ]);
        }

        return view('pages.stock_opname.index', compact('opnames'));
    }

    /**
     * Show form for creating a new stock opname.
     */
    public function create()
    {
        $generatedCode = CodeGenerator::generateStockOpnameCode(StockOpname::class);
        $products = Products::with('unit')->orderBy('prod_name', 'asc')->get();

        return view('pages.stock_opname.create', compact('products', 'generatedCode'));
    }

    /**
     * Store a newly created stock opname in storage.
     */
    public function store(StoreStockOpnameRequest $request)
    {
        $userId = Auth::id() ?? 1;
        $opname = $this->opnameService->createStockOpname($request->validated(), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi Stock Opname berhasil dicatat.',
                'data'    => $opname,
            ], 201);
        }

        $msg = $opname->status_opname === 'COMPLETED' 
            ? 'Transaksi Stock Opname berhasil diselesaikan dan stok produk telah diperbarui.'
            : 'Draft Stock Opname berhasil disimpan.';

        return redirect()->route('stock-opname.index')->with('success', $msg);
    }

    /**
     * Display the specified stock opname detail.
     */
    public function show(Request $request, StockOpname $stockOpname)
    {
        $detailedOpname = $this->opnameService->getStockOpnameById($stockOpname->id);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $detailedOpname,
            ]);
        }

        return view('pages.stock_opname.show', compact('detailedOpname'));
    }

    /**
     * Show form for editing a draft stock opname.
     */
    public function edit(StockOpname $stockOpname)
    {
        $stockOpname->load(['creator', 'items.product.unit']);
        $products = Products::with('unit')->orderBy('prod_name', 'asc')->get();

        return view('pages.stock_opname.edit', compact('stockOpname', 'products'));
    }

    /**
     * Update an existing stock opname in storage.
     */
    public function update(UpdateStockOpnameRequest $request, StockOpname $stockOpname)
    {
        $userId = Auth::id() ?? 1;
        $updatedOpname = $this->opnameService->updateStockOpname($stockOpname, $request->validated(), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi Stock Opname berhasil diperbarui.',
                'data'    => $updatedOpname,
            ]);
        }

        $msg = $updatedOpname->status_opname === 'COMPLETED' 
            ? 'Transaksi Stock Opname berhasil diselesaikan dan stok produk telah diperbarui.'
            : 'Draft Stock Opname berhasil diperbarui.';

        return redirect()->route('stock-opname.index')->with('success', $msg);
    }

    /**
     * Update status of stock opname transaction (e.g. DRAFT -> COMPLETED).
     */
    public function updateStatus(Request $request, StockOpname $stockOpname)
    {
        $request->validate([
            'status_opname' => 'required|in:DRAFT,REVIEW,AWAITING CONFIRMATION,COMPLETED,CONFIRMED',
        ]);

        $userId = Auth::id() ?? 1;
        $updatedOpname = $this->opnameService->updateStatus($stockOpname, $request->input('status_opname'), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Status Stock Opname berhasil diperbarui dan stok telah disesuaikan.',
                'data'    => $updatedOpname,
            ]);
        }

        return redirect()->route('stock-opname.show', $stockOpname->id)->with('success', 'Status Stock Opname berhasil diperbarui.');
    }

    /**
     * Remove the specified stock opname from storage.
     */
    public function destroy(Request $request, StockOpname $stockOpname)
    {
        $this->opnameService->deleteStockOpname($stockOpname);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi Stock Opname berhasil dihapus.',
            ]);
        }

        return redirect()->route('stock-opname.index')->with('success', 'Transaksi Stock Opname berhasil dihapus.');
    }
}
