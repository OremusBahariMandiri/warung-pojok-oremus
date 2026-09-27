<?php

namespace App\Services;

use App\Helpers\CodeGenerator;
use App\Models\Hpp;
use App\Models\ProductHpp;
use App\Models\ProductHppDetail;
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
        $query = Products::with(['unit', 'productHpps.sellingUnit', 'productHpps.details.hpp']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('prod_name', 'like', "%{$search}%")
                  ->orWhere('prod_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['hpp_method'])) {
            $query->whereHas('productHpps', function ($q) use ($filters) {
                $q->where('hpp_method', strtoupper($filters['hpp_method']));
            });
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
        return Products::with(['unit', 'productHpps.sellingUnit', 'productHpps.details.hpp'])->findOrFail($id);
    }

    /**
     * Calculate HPP total based on base purchase price and optional HPP component.
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
     * Create a new product along with its selling configuration (ProductHpp) & component details (ProductHppDetail).
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
            $unitPrice    = (float) ($data['unit_price'] ?? 0);
            $sellingPrice = (float) ($data['selling_price'] ?? 0);

            $product = Products::create([
                'unit_id'       => $data['unit_id'],
                'prod_code'     => $prodCode,
                'prod_name'     => $prodName,
                'slug'          => $slug,
                'initial_stock' => $initialStock,
                'current_stock' => $data['current_stock'] ?? $initialStock,
                'min_stock'     => $data['min_stock'] ?? 5,
                'unit_price'    => $unitPrice,
                'description'   => $data['description'] ?? '',
                'thumbnail'     => $thumbnailPath,
            ]);

            // --- Calculate and Save ProductHpp ---
            $components = $data['components'] ?? [];
            $componentsCostSum = 0.0;
            if ($hppMethod === 'CALCULATED' && is_array($components)) {
                foreach ($components as $comp) {
                    if (!empty($comp['hpp_id'])) {
                        $hppMaster = Hpp::find($comp['hpp_id']);
                        if ($hppMaster) {
                            $componentsCostSum += (float) $hppMaster->unit_cost;
                        } elseif (!empty($comp['cost'])) {
                            $componentsCostSum += (float) $comp['cost'];
                        }
                    }
                }
                $currentHpp = $unitPrice + $componentsCostSum;
            } else {
                $currentHpp = (isset($data['current_hpp']) && $data['current_hpp'] !== '' && $data['current_hpp'] !== null)
                    ? (float) $data['current_hpp']
                    : $unitPrice;
            }

            $productHpp = ProductHpp::create([
                'product_id'      => $product->id,
                'selling_unit_id' => $data['unit_id'],
                'selling_price'   => $sellingPrice,
                'hpp_method'      => $hppMethod,
                'current_hpp'     => $currentHpp,
            ]);

            // --- Save Components to ProductHppDetail ---
            if ($hppMethod === 'CALCULATED' && is_array($components)) {
                foreach ($components as $comp) {
                    if (!empty($comp['hpp_id'])) {
                        ProductHppDetail::create([
                            'product_hpp_id' => $productHpp->id,
                            'hpp_id'         => (int) $comp['hpp_id'],
                        ]);
                    }
                }
            }

            ActivityLogService::log(
                action:      'CREATE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $product->id,
                description: "Created product: {$product->prod_name} (Method: {$hppMethod})",
                oldValues:   null,
                newValues:   $product->load(['productHpps.details.hpp'])->toArray()
            );

            return $product->load(['unit', 'productHpps.sellingUnit', 'productHpps.details.hpp']);
        });
    }

    /**
     * Update an existing product.
     * prod_code & initial_stock tidak diubah saat update.
     */
    public function updateProduct(Products $product, array $data, ?UploadedFile $thumbnail = null): Products
    {
        return DB::transaction(function () use ($product, $data, $thumbnail) {
            $oldValues = $product->load(['productHpps.details.hpp'])->toArray();

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

            $firstHpp = $product->productHpps->first();
            $hppMethod = isset($data['hpp_method']) ? strtoupper($data['hpp_method']) : ($firstHpp?->hpp_method ?? 'MANUAL');

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

            $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : $product->unit_price;

            $product->update([
                'unit_id'       => $data['unit_id']       ?? $product->unit_id,
                'prod_name'     => $prodName,
                'slug'          => $slug,
                'current_stock' => isset($data['current_stock']) ? (int) $data['current_stock'] : $product->current_stock,
                'min_stock'     => isset($data['min_stock'])     ? (int) $data['min_stock']     : $product->min_stock,
                'unit_price'    => $unitPrice,
                'description'   => $data['description'] ?? $product->description,
                'thumbnail'     => $thumbnailPath,
            ]);

            // --- Update ProductHpp and ProductHppDetail ---
            if (isset($data['selling_price']) || isset($data['unit_id'])) {
                $sellingPrice = (float) ($data['selling_price'] ?? 0);

                $components = $data['components'] ?? [];
                $componentsCostSum = 0.0;
                if ($hppMethod === 'CALCULATED' && is_array($components)) {
                    foreach ($components as $comp) {
                        if (!empty($comp['hpp_id'])) {
                            $hppMaster = Hpp::find($comp['hpp_id']);
                            if ($hppMaster) {
                                $componentsCostSum += (float) $hppMaster->unit_cost;
                            } elseif (!empty($comp['cost'])) {
                                $componentsCostSum += (float) $comp['cost'];
                            }
                        }
                    }
                    $currentHpp = $unitPrice + $componentsCostSum;
                } else {
                    $currentHpp = (isset($data['current_hpp']) && $data['current_hpp'] !== '' && $data['current_hpp'] !== null)
                        ? (float) $data['current_hpp']
                        : $unitPrice;
                }

                // Delete old product_hpp and cascading product_hpp_detail
                $oldProductHppIds = ProductHpp::where('product_id', $product->id)->pluck('id');
                ProductHppDetail::whereIn('product_hpp_id', $oldProductHppIds)->delete();
                ProductHpp::where('product_id', $product->id)->delete();

                $productHpp = ProductHpp::create([
                    'product_id'      => $product->id,
                    'selling_unit_id' => $data['unit_id'] ?? $product->unit_id,
                    'selling_price'   => $sellingPrice,
                    'hpp_method'      => $hppMethod,
                    'current_hpp'     => $currentHpp,
                ]);

                if ($hppMethod === 'CALCULATED' && is_array($components)) {
                    foreach ($components as $comp) {
                        if (!empty($comp['hpp_id'])) {
                            ProductHppDetail::create([
                                'product_hpp_id' => $productHpp->id,
                                'hpp_id'         => (int) $comp['hpp_id'],
                            ]);
                        }
                    }
                }
            }

            ActivityLogService::log(
                action:      'UPDATE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $product->id,
                description: "Updated product: {$product->prod_name}",
                oldValues:   $oldValues,
                newValues:   $product->fresh()->load(['productHpps.details.hpp'])->toArray()
            );

            return $product->fresh()->load(['unit', 'productHpps.sellingUnit', 'productHpps.details.hpp']);
        });
    }

    /**
     * Delete a product and its thumbnail.
     */
    public function deleteProduct(Products $product): bool
    {
        return DB::transaction(function () use ($product) {
            $oldValues = $product->load(['productHpps.details.hpp'])->toArray();
            $id        = $product->id;
            $name      = $product->prod_name;

            // Delete thumbnail file if not default
            if (
                $product->thumbnail &&
                !in_array($product->thumbnail, ['thumbnail/default.png', 'products/default.png']) &&
                Storage::disk('public')->exists($product->thumbnail)
            ) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            // Delete detail and product_hpp
            $hppIds = ProductHpp::where('product_id', $product->id)->pluck('id');
            ProductHppDetail::whereIn('product_hpp_id', $hppIds)->delete();
            ProductHpp::where('product_id', $product->id)->delete();

            $deleted = $product->delete();

            ActivityLogService::log(
                action:      'DELETE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $id,
                description: "Deleted product: {$name} (ID: {$id})",
                oldValues:   $oldValues,
                newValues:   null
            );

            return $deleted;
        });
    }
}