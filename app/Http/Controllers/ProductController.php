<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Hpp;
use App\Models\Products;
use App\Models\Unit;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'hpp_method', 'stock_status']);
        $products = $this->productService->getAllProducts($filters);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $products,
            ]);
        }

        return view('pages.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::orderBy('unit_name', 'asc')->get();
        $hppComponents = Hpp::orderBy('name', 'asc')->get();

        return view('pages.products.create', compact('units', 'hppComponents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $thumbnail = $request->file('thumbnail');
        $product = $this->productService->createProduct($request->validated(), $thumbnail);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan.',
                'data' => $product,
            ], 201);
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Products $product)
    {
        $product->load(['unit', 'productHpps.hpp', 'productHpps.sellingUnit']);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $product,
            ]);
        }

        return view('pages.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Products $product)
    {
        $product->load(['unit', 'productHpps.hpp', 'productHpps.sellingUnit']);
        $units = Unit::orderBy('unit_name', 'asc')->get();
        $hppComponents = Hpp::orderBy('name', 'asc')->get();

        return view('pages.products.edit', compact('product', 'units', 'hppComponents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Products $product)
    {
        $thumbnail = $request->file('thumbnail');
        $updatedProduct = $this->productService->updateProduct($product, $request->validated(), $thumbnail);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil diperbarui.',
                'data' => $updatedProduct,
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Products $product)
    {
        $this->productService->deleteProduct($product);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil dihapus.',
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Live calculate HPP based on base purchase price and selected HPP component (AJAX helper).
     */
    public function calculateHpp(Request $request)
    {
        $request->validate([
            'base_purchase_price' => 'required|numeric|min:0',
            'hpp_id'              => 'nullable|exists:hpp,id',
        ]);

        $basePurchasePrice = (float) $request->input('base_purchase_price', 0);
        $hppId             = $request->input('hpp_id');

        $result = $this->productService->calculateHpp($basePurchasePrice, $hppId);

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }
}