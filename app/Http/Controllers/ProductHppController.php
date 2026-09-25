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
     * Display sales configurations for a specific product.
     */
    public function index(Request $request, Products $product)
    {
        $configurations = $product->productHpps()->with(['hpp', 'sellingUnit'])->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $configurations,
            ]);
        }

        return redirect()->route('products.show', $product->id);
    }

    /**
     * Store or sync sales configurations (ProductHpp) for a product.
     */
    public function store(Request $request, Products $product)
    {
        $request->validate([
            'configurations'                     => 'required|array',
            'configurations.*.selling_unit_id'   => 'required|exists:units,id',
            'configurations.*.hpp_id'            => 'nullable|exists:hpp,id',
            'configurations.*.selling_price'     => 'required|numeric|min:0',
            'configurations.*.current_hpp'       => 'required|numeric|min:0',
        ]);

        $configs = $request->input('configurations');

        ProductHpp::where('product_id', $product->id)->delete();
        $savedConfigs = [];
        foreach ($configs as $config) {
            $savedConfigs[] = ProductHpp::create([
                'product_id'      => $product->id,
                'selling_unit_id' => $config['selling_unit_id'],
                'hpp_id'          => !empty($config['hpp_id']) ? $config['hpp_id'] : null,
                'selling_price'   => (float) $config['selling_price'],
                'current_hpp'     => (float) $config['current_hpp'],
            ]);
        }

        ActivityLogService::log(
            action:      'UPDATE_HPP_CONFIGURATIONS',
            module:      'PRODUCT_HPP',
            entityType:  Products::class,
            entityId:    $product->id,
            description: "Updated HPP sales configurations for product: {$product->prod_name}",
            oldValues:   null,
            newValues:   $savedConfigs
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Konfigurasi penjualan produk berhasil diperbarui.',
                'data'    => $product->fresh()->load(['productHpps.hpp', 'productHpps.sellingUnit']),
            ]);
        }

        return redirect()->route('products.show', $product->id)->with('success', 'Konfigurasi penjualan produk berhasil diperbarui.');
    }
}