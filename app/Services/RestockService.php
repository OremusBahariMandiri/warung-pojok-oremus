<?php

namespace App\Services;

use App\Models\Products;
use App\Models\Restock;
use App\Models\RestockItems;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestockService
{
    /**
     * Get all restock transactions with relations and computed summary.
     */
    public function getAllRestocks(array $filters = [])
    {
        $query = Restock::with(['creator', 'items.product'])->orderBy('restock_date', 'desc');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('restock_code', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('restock_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        return $query->get()->map(function ($restock) {
            $totalQuantity = 0;
            $totalValue = 0.0;

            foreach ($restock->items as $item) {
                $product = $item->product;
                $priceUsed = $product ? (float)$product->current_hpp : 0.0;
                $itemSubtotal = $item->quantity * $priceUsed;

                $item->price_used = $priceUsed;
                $item->subtotal = $itemSubtotal;

                $totalQuantity += $item->quantity;
                $totalValue += $itemSubtotal;
            }

            $restock->total_quantity = $totalQuantity;
            $restock->total_value = $totalValue;

            return $restock;
        });
    }

    /**
     * Get single restock detail with computed summary.
     */
    public function getRestockById(int $id): Restock
    {
        $restock = Restock::with(['creator', 'items.product.productHpps.hpp'])->findOrFail($id);

        $totalQuantity = 0;
        $totalValue = 0.0;

        foreach ($restock->items as $item) {
            $product = $item->product;
            $priceUsed = $product ? (float)$product->current_hpp : 0.0;
            $itemSubtotal = $item->quantity * $priceUsed;

            $item->price_used = $priceUsed;
            $item->subtotal = $itemSubtotal;

            $totalQuantity += $item->quantity;
            $totalValue += $itemSubtotal;
        }

        $restock->total_quantity = $totalQuantity;
        $restock->total_value = $totalValue;

        return $restock;
    }

    /**
     * Create a new restock transaction, update product stock, and log activity.
     */
    public function createRestock(array $data, int $userId): Restock
    {
        return DB::transaction(function () use ($data, $userId) {
            $restockCode = !empty($data['restock_code']) ? $data['restock_code'] : $this->generateRestockCode();
            $restockDate = !empty($data['restock_date']) ? Carbon::parse($data['restock_date']) : Carbon::now();

            $restock = Restock::create([
                'created_by' => $userId,
                'restock_code' => $restockCode,
                'restock_date' => $restockDate,
                'supplier_name' => $data['supplier_name'] ?? 'Supplier Umum',
                'notes' => $data['notes'] ?? '',
            ]);

            $itemsSummary = [];
            $totalRestockValue = 0.0;
            $totalQuantity = 0;

            foreach ($data['items'] as $itemData) {
                $productId = $itemData['product_id'];
                $quantity = (int)$itemData['quantity'];
                
                // Lock product row for update to avoid race conditions
                $product = Products::lockForUpdate()->findOrFail($productId);
                $oldStock = $product->current_stock;
                $newStock = $oldStock + $quantity;

                // Price determination based on HPP rules
                if ($product->hpp_method === 'calculated') {
                    // Calculated products use current_hpp from its components
                    $priceUsed = (float)$product->current_hpp;
                } else {
                    // Manual products can update purchase price if provided
                    if (isset($itemData['unit_price']) && $itemData['unit_price'] !== '' && $itemData['unit_price'] !== null) {
                        $priceUsed = (float)$itemData['unit_price'];
                        $product->unit_price = $priceUsed;
                        $product->current_hpp = $priceUsed;
                    } else {
                        $priceUsed = (float)$product->current_hpp;
                    }
                }

                // Update product stock
                $product->current_stock = $newStock;
                $product->save();

                // Save restock item
                $restockItem = RestockItems::create([
                    'restock_id' => $restock->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);

                $subtotal = $quantity * $priceUsed;
                $totalQuantity += $quantity;
                $totalRestockValue += $subtotal;

                $itemsSummary[] = [
                    'product_id' => $productId,
                    'product_name' => $product->prod_name,
                    'sku' => $product->sku,
                    'hpp_method' => $product->hpp_method,
                    'quantity' => $quantity,
                    'price_used' => $priceUsed,
                    'subtotal' => $subtotal,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                ];
            }

            // Log activity
            ActivityLogService::log(
                action: 'CREATE',
                module: 'RESTOCK',
                entityType: Restock::class,
                entityId: $restock->id,
                description: "Created Restock {$restock->restock_code} with {$totalQuantity} items (Total Nilai: Rp " . number_format($totalRestockValue, 0, ',', '.') . ") from {$restock->supplier_name}",
                oldValues: null,
                newValues: [
                    'restock' => $restock->toArray(),
                    'total_quantity' => $totalQuantity,
                    'total_value' => $totalRestockValue,
                    'items' => $itemsSummary,
                ],
                userId: $userId
            );

            $restock->total_quantity = $totalQuantity;
            $restock->total_value = $totalRestockValue;

            return $restock->load(['creator', 'items.product']);
        });
    }

    /**
     * Cancel / Delete a restock transaction and rollback stock additions.
     */
    public function deleteRestock(Restock $restock): bool
    {
        return DB::transaction(function () use ($restock) {
            $restock->load('items.product');
            $oldValues = $restock->toArray();
            $rollbackSummary = [];

            foreach ($restock->items as $item) {
                $product = Products::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $oldStock = $product->current_stock;
                    $newStock = max(0, $oldStock - $item->quantity);
                    $product->current_stock = $newStock;
                    $product->save();

                    $rollbackSummary[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->prod_name,
                        'rolled_back_quantity' => $item->quantity,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                    ];
                }
            }

            $id = $restock->id;
            $code = $restock->restock_code;

            // Restock items will be cascade deleted by FK constraint
            $deleted = $restock->delete();

            ActivityLogService::log(
                action: 'DELETE',
                module: 'RESTOCK',
                entityType: Restock::class,
                entityId: $id,
                description: "Deleted & rolled back Restock transaction: {$code}",
                oldValues: [
                    'restock' => $oldValues,
                    'stock_rollback' => $rollbackSummary,
                ],
                newValues: null
            );

            return $deleted;
        });
    }

    /**
     * Generate unique restock code (format: RST-YYYYMMDD-XXXX).
     */
    private function generateRestockCode(): string
    {
        $datePrefix = Carbon::now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        $code = "RST-{$datePrefix}-{$random}";

        while (Restock::where('restock_code', $code)->exists()) {
            $random = strtoupper(Str::random(4));
            $code = "RST-{$datePrefix}-{$random}";
        }

        return $code;
    }
}
