<?php

namespace App\Services;

use App\Helpers\CodeGenerator;
use App\Models\Hpp;
use App\Models\ProductHpp;
use App\Models\Products;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Get all products with eager loaded HPP relations.
     */
    public function getAllProducts(array $filters = [])
    {
        $query = Products::with(['unit', 'productHpps.hpp', 'productHpps.sellingUnit']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('prod_name', 'like', "%{$search}%")
                  ->orWhere('prod_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['hpp_method'])) {
            $query->where('hpp_method', strtoupper($filters['hpp_method']));
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $query->whereColumn('current_stock', '<=', 'min_stock')->where('current_stock', '>', 0);
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->where('current_stock', '<=', 0);
            } elseif ($filters['stock_status'] === 'in_stock') {
                $query->whereColumn('current_stock', '>', 'min_stock');
            }
        }

        return $query->orderBy('prod_name', 'asc')->get();
    }

    /**
     * Find product by ID with relations.
     */
    public function getProductById(int $id): Products
    {
        return Products::with(['unit', 'productHpps.hpp', 'productHpps.sellingUnit'])->findOrFail($id);
    }

    /**
     * Calculate HPP total based on base purchase price and optional HPP component.
     *
     * Current HPP = Base Purchase Price + Additional HPP Cost (from Master HPP)
     *
     * @param  float  $basePurchasePrice
     * @param  int|null  $hppId
     * @return array  ['base_purchase_price' => float, 'additional_cost' => float, 'current_hpp' => float]
     */
    public function calculateHpp(float $basePurchasePrice, ?int $hppId = null): array
    {
        $additionalCost = 0.0;
        if ($hppId) {
            $hppMaster = Hpp::find($hppId);
            if ($hppMaster) {
                $additionalCost = (float) $hppMaster->unit_cost;
            }
        }

        return [
            'base_purchase_price' => $basePurchasePrice,
            'additional_cost'     => $additionalCost,
            'current_hpp'         => $basePurchasePrice + $additionalCost,
        ];
    }

    /**
     * Create a new product along with its selling configurations (ProductHpp).
     */
    public function createProduct(array $data, ?UploadedFile $thumbnail = null): Products
    {
        return DB::transaction(function () use ($data, $thumbnail) {
            $prodName = $data['prod_name'];

            // --- Slug ---
            $slug         = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($prodName);
            $originalSlug = $slug;
            $counter      = 1;
            while (Products::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }

            // --- prod_code: generate sequential via helper jika tidak diisi ---
            $prodCode = !empty($data['prod_code'])
                ? $data['prod_code']
                : CodeGenerator::generateProductCode(Products::class);

            $hppMethod = strtoupper($data['hpp_method'] ?? 'MANUAL');

            // --- Thumbnail ---
            $thumbnailPath = 'thumbnail/default.png';
            if ($thumbnail) {
                $thumbnailPath = $thumbnail->store('thumbnail', 'public');
            } elseif (!empty($data['thumbnail'])) {
                $thumbnailPath = $data['thumbnail'];
            }

            $initialStock = (int) ($data['initial_stock'] ?? 0);

            $product = Products::create([
                'unit_id'       => $data['unit_id'],
                'prod_code'     => $prodCode,
                'prod_name'     => $prodName,
                'slug'          => $slug,
                'hpp_method'    => $hppMethod,
                'initial_stock' => $initialStock,
                'current_stock' => $data['current_stock'] ?? $initialStock,
                'min_stock'     => $data['min_stock'] ?? 5,
                'description'   => $data['description'] ?? '',
                'thumbnail'     => $thumbnailPath,
            ]);

            // --- Save Sales Configurations (ProductHpp) ---
            $configurations = $data['configurations'] ?? $data['components'] ?? [];
            if (is_array($configurations) && !empty($configurations)) {
                foreach ($configurations as $config) {
                    if (empty($config['selling_unit_id'])) {
                        continue;
                    }

                    ProductHpp::create([
                        'product_id'      => $product->id,
                        'selling_unit_id' => $config['selling_unit_id'],
                        'hpp_id'          => !empty($config['hpp_id']) ? $config['hpp_id'] : null,
                        'selling_price'   => (float) ($config['selling_price'] ?? 0),
                        'current_hpp'     => (float) ($config['current_hpp'] ?? 0),
                    ]);
                }
            }

            ActivityLogService::log(
                action:      'CREATE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $product->id,
                description: "Created product: {$product->prod_name} (Method: {$product->hpp_method})",
                oldValues:   null,
                newValues:   $product->load('productHpps')->toArray()
            );

            return $product->load(['unit', 'productHpps.hpp', 'productHpps.sellingUnit']);
        });
    }

    /**
     * Update an existing product.
     * prod_code & initial_stock tidak diubah saat update.
     */
    public function updateProduct(Products $product, array $data, ?UploadedFile $thumbnail = null): Products
    {
        return DB::transaction(function () use ($product, $data, $thumbnail) {
            $oldValues = $product->load('productHpps')->toArray();

            $prodName = $data['prod_name'] ?? $product->prod_name;

            // --- Slug ---
            $slug         = !empty($data['slug'])
                ? Str::slug($data['slug'])
                : ($prodName !== $product->prod_name ? Str::slug($prodName) : $product->slug);
            $originalSlug = $slug;
            $counter      = 1;
            while (Products::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }

            $hppMethod = isset($data['hpp_method']) ? strtoupper($data['hpp_method']) : $product->hpp_method;

            // --- Thumbnail ---
            $thumbnailPath = $product->thumbnail;
            if ($thumbnail) {
                if (
                    $product->thumbnail &&
                    !in_array($product->thumbnail, ['thumbnail/default.png', 'products/default.png']) &&
                    Storage::disk('public')->exists($product->thumbnail)
                ) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                $thumbnailPath = $thumbnail->store('thumbnail', 'public');
            }

            $product->update([
                'unit_id'       => $data['unit_id']       ?? $product->unit_id,
                'prod_name'     => $prodName,
                'slug'          => $slug,
                'hpp_method'    => $hppMethod,
                'current_stock' => isset($data['current_stock']) ? (int) $data['current_stock'] : $product->current_stock,
                'min_stock'     => isset($data['min_stock'])     ? (int) $data['min_stock']     : $product->min_stock,
                'description'   => $data['description'] ?? $product->description,
                'thumbnail'     => $thumbnailPath,
            ]);

            // --- Update Sales Configurations (ProductHpp) if provided ---
            $configurations = $data['configurations'] ?? $data['components'] ?? null;
            if (is_array($configurations)) {
                ProductHpp::where('product_id', $product->id)->delete();
                foreach ($configurations as $config) {
                    if (empty($config['selling_unit_id'])) {
                        continue;
                    }

                    ProductHpp::create([
                        'product_id'      => $product->id,
                        'selling_unit_id' => $config['selling_unit_id'],
                        'hpp_id'          => !empty($config['hpp_id']) ? $config['hpp_id'] : null,
                        'selling_price'   => (float) ($config['selling_price'] ?? 0),
                        'current_hpp'     => (float) ($config['current_hpp'] ?? 0),
                    ]);
                }
            }

            ActivityLogService::log(
                action:      'UPDATE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $product->id,
                description: "Updated product: {$product->prod_name}",
                oldValues:   $oldValues,
                newValues:   $product->fresh()->load('productHpps')->toArray()
            );

            return $product->fresh()->load(['unit', 'productHpps.hpp', 'productHpps.sellingUnit']);
        });
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Products $product): bool
    {
        return DB::transaction(function () use ($product) {
            $oldValues = $product->load('productHpps')->toArray();
            $id        = $product->id;
            $name      = $product->prod_name;

            if (
                $product->thumbnail &&
                !in_array($product->thumbnail, ['thumbnail/default.png', 'products/default.png']) &&
                Storage::disk('public')->exists($product->thumbnail)
            ) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            $deleted = $product->delete();

            ActivityLogService::log(
                action:      'DELETE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $id,
                description: "Deleted product: {$name}",
                oldValues:   $oldValues,
                newValues:   null
            );

            return $deleted;
        });
    }
}