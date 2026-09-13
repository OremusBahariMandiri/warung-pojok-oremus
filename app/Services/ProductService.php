<?php

namespace App\Services;

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
        $query = Products::with(['productHpps.hpp', 'hpps']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('prod_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['hpp_method'])) {
            $query->where('hpp_method', $filters['hpp_method']);
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
        return Products::with(['productHpps.hpp', 'hpps'])->findOrFail($id);
    }

    /**
     * Calculate HPP total based on unit_price and component list.
     *
     * @param float $unitPrice
     * @param array $components Array of items with keys ['hpp_id', 'cost']
     * @return array ['total_hpp' => float, 'components' => array]
     */
    public function calculateHpp(float $unitPrice, array $components = []): array
    {
        $totalComponentCost = 0.0;
        $normalizedComponents = [];

        foreach ($components as $item) {
            $hppId = $item['hpp_id'] ?? null;
            if (!$hppId) {
                continue;
            }

            // If cost is not explicitly provided, fetch default unit_cost from Hpp master
            if (!isset($item['cost']) || $item['cost'] === '' || $item['cost'] === null) {
                $hppMaster = Hpp::find($hppId);
                $cost = $hppMaster ? (float)$hppMaster->unit_cost : 0.0;
            } else {
                $cost = (float)$item['cost'];
            }

            $totalComponentCost += $cost;
            $normalizedComponents[] = [
                'hpp_id' => $hppId,
                'cost' => $cost,
            ];
        }

        $totalHpp = $unitPrice + $totalComponentCost;

        return [
            'total_hpp' => $totalHpp,
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
            $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($prodName);
            
            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (Products::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }

            $sku = !empty($data['sku']) ? $data['sku'] : $this->generateSku($prodName);
            $unitPrice = (float)($data['unit_price'] ?? 0);
            $sellingPrice = (float)($data['selling_price'] ?? 0);
            $hppMethod = $data['hpp_method'] ?? 'manual';
            $components = $data['components'] ?? [];

            if ($hppMethod === 'calculated' && !empty($components)) {
                $calculation = $this->calculateHpp($unitPrice, $components);
                $currentHpp = $calculation['total_hpp'];
                $processedComponents = $calculation['components'];
            } else {
                $currentHpp = isset($data['current_hpp']) && $data['current_hpp'] !== '' 
                    ? (float)$data['current_hpp'] 
                    : $unitPrice;
                $processedComponents = [];
            }

            $thumbnailPath = 'products/default.png';
            if ($thumbnail) {
                $thumbnailPath = $thumbnail->store('products', 'public');
            } elseif (!empty($data['thumbnail'])) {
                $thumbnailPath = $data['thumbnail'];
            }

            $product = Products::create([
                'prod_name' => $prodName,
                'slug' => $slug,
                'sku' => $sku,
                'satuan' => $data['satuan'] ?? 'Pcs',
                'selling_price' => $sellingPrice,
                'unit_price' => $unitPrice,
                'hpp_method' => $hppMethod,
                'current_hpp' => $currentHpp,
                'current_stock' => $data['current_stock'] ?? 0,
                'min_stock' => $data['min_stock'] ?? 5,
                'description' => $data['description'] ?? '',
                'thumbnail' => $thumbnailPath,
            ]);

            // Save HPP components if calculated
            if ($hppMethod === 'calculated' && !empty($processedComponents)) {
                foreach ($processedComponents as $comp) {
                    ProductHpp::create([
                        'product_id' => $product->id,
                        'hpp_id' => $comp['hpp_id'],
                        'cost' => $comp['cost'],
                    ]);
                }
            }

            ActivityLogService::log(
                action: 'CREATE',
                module: 'PRODUCT',
                entityType: Products::class,
                entityId: $product->id,
                description: "Created product: {$product->prod_name} (HPP: {$product->current_hpp}, Method: {$product->hpp_method})",
                oldValues: null,
                newValues: $product->load('productHpps')->toArray()
            );

            return $product->load(['productHpps.hpp', 'hpps']);
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Products $product, array $data, ?UploadedFile $thumbnail = null): Products
    {
        return DB::transaction(function () use ($product, $data, $thumbnail) {
            $oldValues = $product->load('productHpps')->toArray();

            $prodName = $data['prod_name'] ?? $product->prod_name;
            $slug = !empty($data['slug']) 
                ? Str::slug($data['slug']) 
                : ($prodName !== $product->prod_name ? Str::slug($prodName) : $product->slug);

            $sku = $data['sku'] ?? $product->sku;
            $unitPrice = isset($data['unit_price']) ? (float)$data['unit_price'] : (float)$product->unit_price;
            $sellingPrice = isset($data['selling_price']) ? (float)$data['selling_price'] : (float)$product->selling_price;
            $hppMethod = $data['hpp_method'] ?? $product->hpp_method;
            $components = $data['components'] ?? null;

            if ($hppMethod === 'calculated' && is_array($components)) {
                $calculation = $this->calculateHpp($unitPrice, $components);
                $currentHpp = $calculation['total_hpp'];
                $processedComponents = $calculation['components'];

                // Sync product_hpp records
                ProductHpp::where('product_id', $product->id)->delete();
                foreach ($processedComponents as $comp) {
                    ProductHpp::create([
                        'product_id' => $product->id,
                        'hpp_id' => $comp['hpp_id'],
                        'cost' => $comp['cost'],
                    ]);
                }
            } elseif ($hppMethod === 'manual') {
                $currentHpp = isset($data['current_hpp']) && $data['current_hpp'] !== '' 
                    ? (float)$data['current_hpp'] 
                    : $unitPrice;
                // Remove any components if switched to manual
                ProductHpp::where('product_id', $product->id)->delete();
            } else {
                $currentHpp = $product->current_hpp;
            }

            $thumbnailPath = $product->thumbnail;
            if ($thumbnail) {
                if ($product->thumbnail && $product->thumbnail !== 'products/default.png' && Storage::disk('public')->exists($product->thumbnail)) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                $thumbnailPath = $thumbnail->store('products', 'public');
            }

            $product->update([
                'prod_name' => $prodName,
                'slug' => $slug,
                'sku' => $sku,
                'satuan' => $data['satuan'] ?? $product->satuan,
                'selling_price' => $sellingPrice,
                'unit_price' => $unitPrice,
                'hpp_method' => $hppMethod,
                'current_hpp' => $currentHpp,
                'current_stock' => isset($data['current_stock']) ? (int)$data['current_stock'] : $product->current_stock,
                'min_stock' => isset($data['min_stock']) ? (int)$data['min_stock'] : $product->min_stock,
                'description' => $data['description'] ?? $product->description,
                'thumbnail' => $thumbnailPath,
            ]);

            ActivityLogService::log(
                action: 'UPDATE',
                module: 'PRODUCT',
                entityType: Products::class,
                entityId: $product->id,
                description: "Updated product: {$product->prod_name}",
                oldValues: $oldValues,
                newValues: $product->fresh()->load('productHpps')->toArray()
            );

            return $product->fresh()->load(['productHpps.hpp', 'hpps']);
        });
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Products $product): bool
    {
        return DB::transaction(function () use ($product) {
            $oldValues = $product->load('productHpps')->toArray();
            $id = $product->id;
            $name = $product->prod_name;

            if ($product->thumbnail && $product->thumbnail !== 'products/default.png' && Storage::disk('public')->exists($product->thumbnail)) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            $deleted = $product->delete();

            ActivityLogService::log(
                action: 'DELETE',
                module: 'PRODUCT',
                entityType: Products::class,
                entityId: $id,
                description: "Deleted product: {$name}",
                oldValues: $oldValues,
                newValues: null
            );

            return $deleted;
        });
    }

    /**
     * Generate unique SKU.
     */
    private function generateSku(string $prodName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $prodName), 0, 3));
        if (strlen($prefix) < 3) {
            $prefix = 'PRD';
        }
        $random = strtoupper(Str::random(4));
        $sku = "{$prefix}-{$random}";

        while (Products::where('sku', $sku)->exists()) {
            $random = strtoupper(Str::random(4));
            $sku = "{$prefix}-{$random}";
        }

        return $sku;
    }
}
