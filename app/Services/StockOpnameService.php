<?php

namespace App\Services;

use App\Helpers\CodeGenerator;
use App\Models\Products;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpnameService
{
    /**
     * Normalize status input for stock_opname table enum.
     */
    private function normalizeStatus(string $status): string
    {
        $status = strtoupper($status);
        if ($status === 'CONFIRMED') {
            return 'COMPLETED';
        }
        return $status;
    }

    /**
     * Get all stock opname records.
     */
    public function getAllStockOpnames(array $filters = [])
    {
        $query = StockOpname::with(['creator', 'items.product.unit'])->orderBy('opname_date', 'desc');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('opname_code', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status_opname'])) {
            $query->where('status_opname', $this->normalizeStatus($filters['status_opname']));
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('opname_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }

        return $query->get();
    }

    /**
     * Get single stock opname detail.
     */
    public function getStockOpnameById(int $id): StockOpname
    {
        return StockOpname::with(['creator', 'items.product.unit'])->findOrFail($id);
    }

    /**
     * Create a new stock opname transaction.
     * Difference = physical_stock - initial_stock (initial stock is the permanent baseline).
     * Opname does NOT modify current_stock — that is solely managed by restock operations.
     */
    public function createStockOpname(array $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($data, $userId) {
            $opnameCode = !empty($data['opname_code']) 
                ? $data['opname_code'] 
                : CodeGenerator::generateStockOpnameCode(StockOpname::class);
            $opnameDate = !empty($data['opname_date']) ? Carbon::parse($data['opname_date']) : Carbon::now();
            $status     = $this->normalizeStatus($data['status_opname'] ?? 'DRAFT');

            $opname = StockOpname::create([
                'created_by'    => $userId,
                'opname_code'   => $opnameCode,
                'opname_date'   => $opnameDate,
                'notes'         => $data['notes'] ?? '',
                'status_opname' => $status,
            ]);

            $itemsSummary = [];

            foreach ($data['items'] as $itemData) {
                $productId     = (int) $itemData['product_id'];
                $physicalStock = (int) $itemData['physical_stock'];

                // Lock product row — system_stock is current_stock for display/diff calculation
                $product     = Products::lockForUpdate()->findOrFail($productId);
                $systemStock = (int) $product->current_stock;
                $difference  = $physicalStock - $systemStock;

                StockOpnameItem::create([
                    'opname_id'           => $opname->id,
                    'product_id'          => $productId,
                    'system_stock'        => $systemStock,
                    'physical_stock'      => $physicalStock,
                    'difference'          => $difference,
                    // Save old initial_stock here so rollback can restore it
                    'stock_before_opname' => (int) $product->initial_stock,
                ]);

                // If COMPLETED: update initial_stock to the physical count result
                if ($status === 'COMPLETED') {
                    $product->initial_stock = $physicalStock;
                    $product->save();
                }

                $itemsSummary[] = [
                    'product_id'     => $productId,
                    'product_name'   => $product->prod_name,
                    'system_stock'   => $systemStock,
                    'physical_stock' => $physicalStock,
                    'difference'     => $difference,
                ];
            }



            ActivityLogService::log(
                action:      'CREATE',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Created Stock Opname {$opname->opname_code} (Status: {$status}, Items: " . count($itemsSummary) . ")",
                oldValues:   null,
                newValues:   [
                    'opname' => $opname->toArray(),
                    'items'  => $itemsSummary,
                ],
                userId:      $userId
            );

            return $opname->load(['creator', 'items.product.unit']);
        });
    }

    /**
     * Update an existing stock opname transaction (e.g. from DRAFT).
     */
    public function updateStockOpname(StockOpname $opname, array $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $userId) {
            $oldValues = $opname->load('items')->toArray();
            $oldStatus = $opname->status_opname;
            $newStatus = $this->normalizeStatus($data['status_opname'] ?? $oldStatus);

            $opname->update([
                'opname_date'   => !empty($data['opname_date']) ? Carbon::parse($data['opname_date']) : $opname->opname_date,
                'notes'         => $data['notes'] ?? $opname->notes,
                'status_opname' => $newStatus,
            ]);

            // Re-sync items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $opname->items()->delete();

                $itemsSummary = [];
                foreach ($data['items'] as $itemData) {
                    $productId     = (int) $itemData['product_id'];
                    $physicalStock = (int) $itemData['physical_stock'];

                    $product     = Products::lockForUpdate()->findOrFail($productId);
                    $systemStock = (int) $product->current_stock;
                    $difference  = $physicalStock - $systemStock;

                    StockOpnameItem::create([
                        'opname_id'           => $opname->id,
                        'product_id'          => $productId,
                        'system_stock'        => $systemStock,
                        'physical_stock'      => $physicalStock,
                        'difference'          => $difference,
                        // Save old initial_stock so rollback can restore it
                        'stock_before_opname' => (int) $product->initial_stock,
                    ]);

                    // If COMPLETED: update initial_stock to the physical count result
                    if ($newStatus === 'COMPLETED') {
                        $product->initial_stock = $physicalStock;
                        $product->save();
                    }

                    $itemsSummary[] = [
                        'product_id'     => $productId,
                        'product_name'   => $product->prod_name,
                        'system_stock'   => $systemStock,
                        'physical_stock' => $physicalStock,
                        'difference'     => $difference,
                    ];
                }
            }



            ActivityLogService::log(
                action:      'UPDATE',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Updated Stock Opname {$opname->opname_code} (Status: {$newStatus})",
                oldValues:   $oldValues,
                newValues:   $opname->fresh()->load('items')->toArray(),
                userId:      $userId
            );

            return $opname->fresh()->load(['creator', 'items.product.unit']);
        });
    }

    /**
     * Update status of stock opname transaction (e.g. DRAFT -> COMPLETED).
     * Opname does NOT modify current_stock regardless of status change.
     */
    public function updateStatus(StockOpname $opname, string $newStatus, int $userId): StockOpname
    {
        return DB::transaction(function () use ($opname, $newStatus, $userId) {
            $oldStatus = $opname->status_opname;
            $newStatus = $this->normalizeStatus($newStatus);

            if ($oldStatus === $newStatus) {
                return $opname;
            }

            $opname->load('items');

            // If DRAFT/REVIEW → COMPLETED: update initial_stock to physical_stock result
            if ($oldStatus !== 'COMPLETED' && $newStatus === 'COMPLETED') {
                foreach ($opname->items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->initial_stock = $item->physical_stock;
                        $product->save();
                    }
                }
            }

            $opname->update(['status_opname' => $newStatus]);


            ActivityLogService::log(
                action:      'UPDATE_STATUS',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Updated Stock Opname {$opname->opname_code} status from {$oldStatus} to {$newStatus}",
                oldValues:   ['status_opname' => $oldStatus],
                newValues:   ['status_opname' => $newStatus],
                userId:      $userId
            );

            return $opname->fresh()->load(['creator', 'items.product.unit']);
        });
    }

    /**
     * Delete / Rollback a stock opname record.
     * No current_stock restore needed — opname never modifies current_stock.
     */
    public function deleteStockOpname(StockOpname $opname): bool
    {
        return DB::transaction(function () use ($opname) {
            $opname->load('items');
            $oldValues = $opname->toArray();
            $code = $opname->opname_code;

            // If COMPLETED: restore initial_stock back to the value before opname
            if ($opname->status_opname === 'COMPLETED') {
                foreach ($opname->items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->initial_stock = $item->stock_before_opname;
                        $product->save();
                    }
                }
            }

            $deleted = $opname->delete();


            ActivityLogService::log(
                action:      'DELETE',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Rolled back / Deleted Stock Opname transaction: {$code}",
                oldValues:   $oldValues,
                newValues:   null
            );

            return $deleted;
        });
    }
}
