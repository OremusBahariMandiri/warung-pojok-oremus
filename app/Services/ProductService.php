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
        $query = Products::with(['unit', 'productHpps.hpp', 'productHpps.sellingUnit', 'hpps']);

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
        return Products::with(['unit', 'productHpps.hpp', 'productHpps.sellingUnit', 'hpps'])->findOrFail($id);
    }

    /**
     * Calculate HPP total based on unit_price and component list.
     *
     * Kolom pivot product_hpp sekarang menggunakan selling_price (bukan cost).
     * selling_price per komponen = harga jual komponen tersebut dalam konteks produk ini.
     * Jika tidak diisi, fallback ke unit_cost dari master hpp.
     *
     * @param  float  $unitPrice
     * @param  array  $components  [['hpp_id' => int, 'selling_unit_id' => int, 'selling_price' => float|null], ...]
     * @return array  ['total_hpp' => float, 'components' => array]
     */
    public function calculateHpp(float $unitPrice, array $components = []): array
    {
        $totalComponentCost   = 0.0;
        $normalizedComponents = [];

        foreach ($components as $item) {
            $hppId          = $item['hpp_id'] ?? null;
            $sellingUnitId  = $item['selling_unit_id'] ?? null;

            if (!$hppId || !$sellingUnitId) {
                continue;
            }

            // Jika selling_price tidak diisi, fallback ke unit_cost dari master hpp
            if (!isset($item['selling_price']) || $item['selling_price'] === '' || $item['selling_price'] === null) {
                $hppMaster    = Hpp::find($hppId);
                $sellingPrice = $hppMaster ? (float) $hppMaster->unit_cost : 0.0;
            } else {
                $sellingPrice = (float) $item['selling_price'];
            }

            $totalComponentCost    += $sellingPrice;
            $normalizedComponents[] = [
                'hpp_id'          => $hppId,
                'selling_unit_id' => $sellingUnitId,
                'selling_price'   => $sellingPrice,
            ];
        }

        return [
            'total_hpp'  => $unitPrice + $totalComponentCost,
            'components' => $normalizedComponents,
        ];
    }

    /**
     * Create a new product.
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

            // --- HPP ---
            $unitPrice  = (float) ($data['unit_price'] ?? 0);
            $hppMethod  = strtoupper($data['hpp_method'] ?? 'MANUAL');
            $components = $data['components'] ?? [];

            if ($hppMethod === 'CALCULATED' && !empty($components)) {
                $calculation         = $this->calculateHpp($unitPrice, $components);
                $currentHpp          = $calculation['total_hpp'];
                $processedComponents = $calculation['components'];
            } else {
                $currentHpp = isset($data['current_hpp']) && $data['current_hpp'] !== ''
                    ? (float) $data['current_hpp']
                    : $unitPrice;
                $processedComponents = [];
            }

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
                'current_hpp'   => $currentHpp,
                'current_stock' => $data['current_stock'] ?? $initialStock,
                'min_stock'     => $data['min_stock'] ?? 5,
                'unit_price'    => $unitPrice,
                'description'   => $data['description'] ?? '',
                'thumbnail'     => $thumbnailPath,
            ]);

            // --- Save HPP components ---
            if ($hppMethod === 'CALCULATED' && !empty($processedComponents)) {
                foreach ($processedComponents as $comp) {
                    ProductHpp::create([
                        'product_id'      => $product->id,
                        'hpp_id'          => $comp['hpp_id'],
                        'selling_unit_id' => $comp['selling_unit_id'],
                        'selling_price'   => $comp['selling_price'],
                    ]);
                }
            }

            ActivityLogService::log(
                action:      'CREATE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $product->id,
                description: "Created product: {$product->prod_name} (HPP: {$product->current_hpp}, Method: {$product->hpp_method})",
                oldValues:   null,
                newValues:   $product->load('productHpps')->toArray()
            );

            return $product->load(['unit', 'productHpps.hpp', 'productHpps.sellingUnit', 'hpps']);
        });
    }

    /**
     * Update an existing product.
     * prod_code & initial_stock tidak pernah diubah saat update.
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

            // --- HPP ---
            $unitPrice  = isset($data['unit_price']) ? (float) $data['unit_price'] : (float) $product->unit_price;
            $hppMethod  = isset($data['hpp_method']) ? strtoupper($data['hpp_method']) : $product->hpp_method;
            $components = $data['components'] ?? null;

            if ($hppMethod === 'CALCULATED' && is_array($components)) {
                $calculation         = $this->calculateHpp($unitPrice, $components);
                $currentHpp          = $calculation['total_hpp'];
                $processedComponents = $calculation['components'];

                ProductHpp::where('product_id', $product->id)->delete();
                foreach ($processedComponents as $comp) {
                    ProductHpp::create([
                        'product_id'      => $product->id,
                        'hpp_id'          => $comp['hpp_id'],
                        'selling_unit_id' => $comp['selling_unit_id'],
                        'selling_price'   => $comp['selling_price'],
                    ]);
                }
            } elseif ($hppMethod === 'MANUAL') {
                $currentHpp = isset($data['current_hpp']) && $data['current_hpp'] !== ''
                    ? (float) $data['current_hpp']
                    : $unitPrice;
                ProductHpp::where('product_id', $product->id)->delete();
            } else {
                $currentHpp = $product->current_hpp;
            }

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
                'current_hpp'   => $currentHpp,
                'current_stock' => isset($data['current_stock']) ? (int) $data['current_stock'] : $product->current_stock,
                'min_stock'     => isset($data['min_stock'])     ? (int) $data['min_stock']     : $product->min_stock,
                'unit_price'    => $unitPrice,
                'description'   => $data['description'] ?? $product->description,
                'thumbnail'     => $thumbnailPath,
                // prod_code    → tidak diubah
                // initial_stock → tidak diubah
            ]);

            ActivityLogService::log(
                action:      'UPDATE',
                module:      'PRODUCT',
                entityType:  Products::class,
                entityId:    $product->id,
                description: "Updated product: {$product->prod_name}",
                oldValues:   $oldValues,
                newValues:   $product->fresh()->load('productHpps')->toArray()
            );

            return $product->fresh()->load(['unit', 'productHpps.hpp', 'productHpps.sellingUnit', 'hpps']);
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