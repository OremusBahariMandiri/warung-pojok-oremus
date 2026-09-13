<?php

namespace App\Http\Controllers;

use App\Models\ProductHpp;
use App\Models\Products;
use App\Services\ActivityLogService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductHppController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display components for a specific product.
     */
    public function index(Request $request, Products $product)
    {
        $components = $product->productHpps()->with('hpp')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $components,
            ]);
        }

        return view('pages.products.components', compact('product', 'components'));
    }

    /**
     * Store or sync components for a product.
     */
    public function store(Request $request, Products $product)
    {
        $request->validate([
            'components' => 'required|array',
            'components.*.hpp_id' => 'required|exists:hpp,id',
            'components.*.cost' => 'nullable|numeric|min:0',
        ]);

        $calculation = $this->productService->calculateHpp((float)$product->unit_price, $request->input('components'));
        
        ProductHpp::where('product_id', $product->id)->delete();
        foreach ($calculation['components'] as $comp) {
            ProductHpp::create([
                'product_id' => $product->id,
                'hpp_id' => $comp['hpp_id'],
                'cost' => $comp['cost'],
            ]);
        }

        $product->update([
            'hpp_method' => 'calculated',
            'current_hpp' => $calculation['total_hpp'],
        ]);

        ActivityLogService::log(
            action: 'UPDATE_HPP_COMPONENTS',
            module: 'PRODUCT_HPP',
            entityType: Products::class,
            entityId: $product->id,
            description: "Updated HPP components for product: {$product->prod_name} (New HPP: {$calculation['total_hpp']})",
            oldValues: null,
            newValues: $calculation
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Komposisi HPP produk berhasil diperbarui.',
                'data' => $product->fresh()->load('productHpps.hpp'),
            ]);
        }

        return redirect()->route('products.show', $product->id)->with('success', 'Komposisi HPP produk berhasil diperbarui.');
    }
}
