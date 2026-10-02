<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Http\Requests\UpdateStockOpnameRequest;
use App\Models\Products;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Carbon\Carbon;
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
        $products      = Products::with('unit')->orderBy('prod_name', 'asc')->get();

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
        $userId        = Auth::id() ?? 1;
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

        $userId        = Auth::id() ?? 1;
        $updatedOpname = $this->opnameService->updateStatus($stockOpname, $request->input('status_opname'), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Status Stock Opname berhasil diperbarui dan stok telah disesuaikan.',
                'data'    => $updatedOpname,
            ]);
        }

        return redirect()->route('stock-opname.show', $stockOpname->id)
            ->with('success', 'Status Stock Opname berhasil diperbarui.');
    }

    /**
     * Rollback: kembalikan COMPLETED opname ke status DRAFT.
     *
     * Data opname & items TIDAK dihapus — hanya:
     *  1. initial_stock produk dikembalikan ke nilai sebelum opname.
     *  2. Status opname dikembalikan ke DRAFT.
     *
     * Setelah rollback admin/user bisa membuka edit form dan memperbaiki data.
     *
     * Endpoint: DELETE /stock-opname/{stockOpname}
     * (Dipakai oleh tombol "Rollback Opname" di blade — dalam window 3 jam)
     */
    public function destroy(Request $request, StockOpname $stockOpname)
    {
        // Cek apakah masih dalam window 3 jam sejak updated_at / opname_date
        $completedAt      = $stockOpname->updated_at ?? $stockOpname->opname_date;
        $rollbackDeadline = $completedAt ? $completedAt->copy()->addHours(3) : null;

        if ($stockOpname->status_opname === 'COMPLETED') {
            // Validasi window rollback
            if (!$rollbackDeadline || now()->greaterThanOrEqualTo($rollbackDeadline)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Batas waktu rollback (3 jam) sudah terlewat. Transaksi tidak dapat di-rollback.',
                    ], 422);
                }

                return redirect()->route('stock-opname.edit', $stockOpname->id)
                    ->with('error', 'Batas waktu rollback (3 jam) sudah terlewat. Transaksi tidak dapat di-rollback.');
            }

            // Lakukan rollback — data tetap ada, status kembali DRAFT
            $userId = Auth::id() ?? 1;
            $this->opnameService->rollbackStockOpname($stockOpname, $userId);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Stock Opname berhasil di-rollback. Status kembali ke DRAFT dan stok produk telah dipulihkan.',
                ]);
            }

            return redirect()->route('stock-opname.index', $stockOpname->id)
                ->with('success', 'Stock Opname berhasil di-rollback. Silakan periksa dan perbaiki data sebelum menyelesaikannya kembali.');
        }

        // Jika bukan COMPLETED (masih DRAFT) — hard delete diizinkan
        $this->opnameService->deleteStockOpname($stockOpname);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi Stock Opname berhasil dihapus.',
            ]);
        }

        return redirect()->route('stock-opname.index')
            ->with('success', 'Transaksi Stock Opname berhasil dihapus.');
    }
}