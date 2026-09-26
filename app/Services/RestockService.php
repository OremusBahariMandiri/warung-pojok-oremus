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
     * Get all restock transactions with relations.
     */
    public function getAllRestocks(array $filters = [])
    {
        $query = Restock::with(['creator', 'items.product', 'items.unit'])->orderBy('restock_date', 'desc');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('restock_code', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status_restock'])) {
            $query->where('status_restock', strtoupper($filters['status_restock']));
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('restock_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        return $query->get();
    }

    /**
     * Get single restock detail with relations.
     */
    public function getRestockById(int $id): Restock
    {
        return Restock::with(['creator', 'items.product', 'items.unit'])->findOrFail($id);
    }

    /**
     * Create a new restock transaction.
     * If status_restock is CONFIRMED, current_stock on master product is incremented.
     */
    public function createRestock(array $data, int $userId): Restock
    {
        return DB::transaction(function () use ($data, $userId) {
            $restockCode = !empty($data['restock_code']) ? $data['restock_code'] : $this->generateRestockCode();
            $invoiceNum  = !empty($data['invoice_number']) ? $data['invoice_number'] : "INV-" . Carbon::now()->format('YmdHis');
            $restockDate = !empty($data['restock_date']) ? Carbon::parse($data['restock_date']) : Carbon::now();
            $status      = strtoupper($data['status_restock'] ?? 'DRAFT');

            $subtotal = 0.0;
            $itemsToCreate = [];

            foreach ($data['items'] as $itemData) {
                $productId     = (int) $itemData['product_id'];
                $restockUnitId = (int) $itemData['restock_unit_id'];
                $quantity      = (int) $itemData['quantity'];
                $purchasePrice = (float) $itemData['purchase_price'];
                $itemTotal     = $quantity * $purchasePrice;

                $subtotal += $itemTotal;

                $itemsToCreate[] = [
                    'product_id'      => $productId,
                    'restock_unit_id' => $restockUnitId,
                    'quantity'        => $quantity,
                    'purchase_price'  => $purchasePrice,
                    'total_price'     => $itemTotal,
                ];
            }

            $discount   = (float) ($data['discount'] ?? 0);
            $grandTotal = max(0.0, $subtotal - $discount);

            $restock = Restock::create([
                'created_by'     => $userId,
                'restock_code'   => $restockCode,
                'invoice_number' => $invoiceNum,
                'restock_date'   => $restockDate,
                'supplier_name'  => $data['supplier_name'] ?? 'Supplier Umum',
                'status_restock' => $status,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'grand_total'    => $grandTotal,
                'notes'          => $data['notes'] ?? '',
            ]);

            foreach ($itemsToCreate as $itemData) {
                RestockItems::create([
                    'restock_id'      => $restock->id,
                    'product_id'      => $itemData['product_id'],
                    'restock_unit_id' => $itemData['restock_unit_id'],
                    'quantity'        => $itemData['quantity'],
                    'purchase_price'  => $itemData['purchase_price'],
                    'total_price'     => $itemData['total_price'],
                ]);

                // If transaction is CONFIRMED, add to product current_stock
                if ($status === 'CONFIRMED') {
                    $product = Products::lockForUpdate()->find($itemData['product_id']);
                    if ($product) {
                        $product->current_stock = $product->current_stock + $itemData['quantity'];
                        $product->save();
                    }
                }
            }

            ActivityLogService::log(
                action:      'CREATE',
                module:      'RESTOCK',
                entityType:  Restock::class,
                entityId:    $restock->id,
                description: "Created Restock {$restock->restock_code} (Status: {$status}, Grand Total: Rp " . number_format($grandTotal, 0, ',', '.') . ")",
                oldValues:   null,
                newValues:   $restock->load(['creator', 'items.product', 'items.unit'])->toArray(),
                userId:      $userId
            );

            return $restock->load(['creator', 'items.product', 'items.unit']);
        });
    }

    /**
     * Update status of restock transaction (e.g. from DRAFT to CONFIRMED).
     */
    public function updateStatus(Restock $restock, string $newStatus, int $userId): Restock
    {
        return DB::transaction(function () use ($restock, $newStatus, $userId) {
            $oldStatus = $restock->status_restock;
            $newStatus = strtoupper($newStatus);

            if ($oldStatus === $newStatus) {
                return $restock;
            }

            $restock->load('items');

            // If changing DRAFT -> CONFIRMED: add stock
            if ($oldStatus === 'DRAFT' && $newStatus === 'CONFIRMED') {
                foreach ($restock->items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->current_stock = $product->current_stock + $item->quantity;
                        $product->save();
                    }
                }
            }
            // If changing CONFIRMED -> DRAFT: rollback stock addition
            elseif ($oldStatus === 'CONFIRMED' && $newStatus === 'DRAFT') {
                foreach ($restock->items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->current_stock = max(0, $product->current_stock - $item->quantity);
                        $product->save();
                    }
                }
            }

            $restock->update(['status_restock' => $newStatus]);

            ActivityLogService::log(
                action:      'UPDATE_STATUS',
                module:      'RESTOCK',
                entityType:  Restock::class,
                entityId:    $restock->id,
                description: "Updated Restock {$restock->restock_code} status from {$oldStatus} to {$newStatus}",
                oldValues:   ['status_restock' => $oldStatus],
                newValues:   ['status_restock' => $newStatus],
                userId:      $userId
            );

            return $restock->fresh()->load(['creator', 'items.product', 'items.unit']);
        });
    }

    /**
     * Delete a restock transaction and rollback stock additions if CONFIRMED.
     */
    public function deleteRestock(Restock $restock): bool
    {
        return DB::transaction(function () use ($restock) {
            $restock->load('items');
            $oldValues = $restock->toArray();
            $code = $restock->restock_code;

            // Rollback stock additions if was CONFIRMED
            if ($restock->status_restock === 'CONFIRMED') {
                foreach ($restock->items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->current_stock = max(0, $product->current_stock - $item->quantity);
                        $product->save();
                    }
                }
            }

            $deleted = $restock->delete();

            ActivityLogService::log(
                action:      'DELETE',
                module:      'RESTOCK',
                entityType:  Restock::class,
                entityId:    $restock->id,
                description: "Deleted Restock transaction: {$code}",
                oldValues:   $oldValues,
                newValues:   null
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
