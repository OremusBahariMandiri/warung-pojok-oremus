<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRestockRequest;
use App\Models\Products;
use App\Models\Restock;
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
        $filters = $request->only(['search', 'start_date', 'end_date']);
        $restocks = $this->restockService->getAllRestocks($filters);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $restocks,
            ]);
        }

        return view('pages.restock.index', compact('restocks'));
    }

    /**
     * Show the form for creating a new restock transaction.
     */
    public function create()
    {
        $products = Products::with('unit')->select([
            'id', 'unit_id', 'prod_name', 'sku', 'hpp_method', 'unit_price', 'current_hpp', 'current_stock'
        ])->orderBy('prod_name', 'asc')->get();

        return view('pages.restock.create', compact('products'));
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
                'status' => 'success',
                'message' => 'Transaksi restock berhasil dicatat dan stok telah ditambahkan.',
                'data' => $restock,
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
                'data' => $detailedRestock,
            ]);
        }

        return view('pages.restock.show', compact('detailedRestock'));
    }

    /**
     * Remove / Rollback the specified restock from storage.
     */
    public function destroy(Request $request, Restock $restock)
    {
        $this->restockService->deleteRestock($restock);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi restock berhasil dibatalkan dan penambahan stok telah di-rollback.',
            ]);
        }

        return redirect()->route('restock.index')->with('success', 'Transaksi restock berhasil dibatalkan.');
    }
}
