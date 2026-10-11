<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Http\Requests\StoreRestockRequest;
use App\Http\Requests\UpdateRestockRequest;
use App\Models\Products;
use App\Models\Restock;
use App\Models\Unit;
use App\Services\RestockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestockController extends Controller
{
    protected RestockService $restockService;

    public function __construct(RestockService $restockService)
    {
        $this->restockService = $restockService;
    }

    /**
     * Display a listing of the restock transactions.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status_restock', 'start_date', 'end_date']);
        $restocks = $this->restockService->getAllRestocks($filters);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $restocks,
            ]);
        }

        return view('pages.restock.index', compact('restocks'));
    }

    /**
     * Show the form for creating a new restock transaction.
     */
    public function create()
    {
        $products         = Products::with(['unit', 'productHpps.sellingUnit'])->orderBy('prod_name', 'asc')->get();
        $units            = Unit::orderBy('unit_name', 'asc')->get();
        // $generatedInvoice = CodeGenerator::generateInvoiceNumber(Restock::class);
        $generatedCode    = CodeGenerator::generateRestockCode(Restock::class);

        return view('pages.restock.create', compact('products', 'units', 'generatedCode'));
    }

    /**
     * Store a newly created restock in storage.
     */
    public function store(StoreRestockRequest $request)
    {
        $userId = Auth::id() ?? 1;
        $restock = $this->restockService->createRestock($request->validated(), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi restock berhasil dicatat.',
                'data'    => $restock,
            ], 201);
        }

        return redirect()->route('restock.index')->with('success', 'Transaksi restock berhasil disimpan.');
    }

    /**
     * Display the specified restock detail.
     */
    public function show(Request $request, Restock $restock)
    {
        $detailedRestock = $this->restockService->getRestockById($restock->id);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $detailedRestock,
            ]);
        }

        return view('pages.restock.show', compact('detailedRestock'));
    }

    /**
     * Show the form for editing the specified restock transaction.
     */
    public function edit(Restock $restock)
    {
        $restock->load(['creator', 'items.product.unit', 'items.unit']);
        $products = Products::with(['unit', 'productHpps.sellingUnit'])->orderBy('prod_name', 'asc')->get();
        $units    = Unit::orderBy('unit_name', 'asc')->get();

        return view('pages.restock.edit', compact('restock', 'products', 'units'));
    }

    /**
     * Update the specified restock transaction in storage.
     */
    public function update(UpdateRestockRequest $request, Restock $restock)
    {
        $userId = Auth::id() ?? 1;
        $updatedRestock = $this->restockService->updateRestock($restock, $request->validated(), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi restock berhasil diperbarui.',
                'data'    => $updatedRestock,
            ]);
        }

        return redirect()->route('restock.index')->with('success', 'Transaksi restock berhasil diperbarui.');
    }

    /**
     * Update status of restock transaction (DRAFT -> CONFIRMED).
     */
    public function updateStatus(Request $request, Restock $restock)
    {
        $request->validate([
            'status_restock' => 'required|in:DRAFT,CONFIRMED',
        ]);

        $userId = Auth::id() ?? 1;
        $updatedRestock = $this->restockService->updateStatus($restock, $request->input('status_restock'), $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Status restock berhasil diperbarui.',
                'data'    => $updatedRestock,
            ]);
        }

        return redirect()->route('restock.show', $restock->id)->with('success', 'Status restock berhasil diperbarui.');
    }

    /**
     * Rollback restock transaction (CONFIRMED -> DRAFT):
     * Mengembalikan penambahan stok dan harga produk ke sebelum transaksi ini dikonfirmasi.
     * Status kembali menjadi DRAFT. Data restock TIDAK dihapus.
     * Dapat dilakukan oleh Super Admin maupun Non-Admin yang memiliki izin.
     */
    public function rollback(Request $request, Restock $restock)
    {
        $confirmedAt      = $restock->updated_at ?? $restock->restock_date;
        $rollbackDeadline = $confirmedAt ? $confirmedAt->copy()->addHours(3) : null;

        if ($restock->status_restock === 'CONFIRMED') {
            if (!$rollbackDeadline || now()->greaterThanOrEqualTo($rollbackDeadline)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Batas waktu rollback (3 jam) sudah terlewat. Transaksi tidak dapat di-rollback.',
                    ], 422);
                }

                return redirect()->route('restock.edit', $restock->id)
                    ->with('error', 'Batas waktu rollback (3 jam) sudah terlewat. Transaksi tidak dapat di-rollback.');
            }

            $this->restockService->deleteRestock($restock);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Transaksi restock berhasil dibatalkan dan penambahan stok telah di-rollback.',
                ]);
            }

            return redirect()->route('restock.index')
                ->with('success', 'Transaksi restock berhasil di-rollback dan status kembali ke DRAFT.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya transaksi berstatus CONFIRMED yang dapat di-rollback.',
            ], 422);
        }

        return redirect()->route('restock.edit', $restock->id)
            ->with('error', 'Hanya transaksi berstatus CONFIRMED yang dapat di-rollback.');
    }

    /**
     * Remove / Rollback the specified restock from storage.
     * Jika aksi rollback dikirimkan, jalankan alur rollback.
     * Jika dari tombol Trash Index oleh Super Admin (is_admin = 1), hapus data transaksi secara permanen.
     */
    public function destroy(Request $request, Restock $restock)
    {
        if ($request->input('action_type') === 'rollback') {
            return $this->rollback($request, $restock);
        }

        if (Auth::check() && (int) Auth::user()->is_admin === 1) {
            $this->restockService->permanentDeleteRestock($restock);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Transaksi restock berhasil dihapus permanen oleh Super Admin.',
                ]);
            }

            return redirect()->route('restock.index')->with('success', 'Transaksi restock berhasil dihapus permanen.');
        }

        $this->restockService->deleteRestock($restock);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi restock berhasil dibatalkan dan penambahan stok telah di-rollback.',
            ]);
        }

        return redirect()->route('restock.index')->with('success', 'Transaksi restock berhasil dibatalkan.');
    }
}