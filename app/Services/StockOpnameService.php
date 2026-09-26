<?php

namespace App\Services;

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
        $query = StockOpname::with(['creator', 'items.product'])->orderBy('opname_date', 'desc');

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
        return StockOpname::with(['creator', 'items.product'])->findOrFail($id);
    }

    /**
     * Create a new stock opname transaction.
     * Difference = physical_stock - system_stock.
     * If status_opname is COMPLETED or CONFIRMED, current_stock on master product is adjusted to physical_stock.
     */
    public function createStockOpname(array $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($data, $userId) {
            $opnameCode = !empty($data['opname_code']) ? $data['opname_code'] : $this->generateOpnameCode();
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

                // Lock product row to get current system stock
                $product     = Products::lockForUpdate()->findOrFail($productId);
                $systemStock = $product->current_stock;
                $difference  = $physicalStock - $systemStock;

                StockOpnameItem::create([
                    'opname_id'      => $opname->id,
                    'product_id'     => $productId,
                    'system_stock'   => $systemStock,
                    'physical_stock' => $physicalStock,
                    'difference'     => $difference,
                ]);

                // If transaction is COMPLETED, adjust product current_stock to physical_stock
                if ($status === 'COMPLETED') {
                    $product->current_stock = $physicalStock;
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

            return $opname->load(['creator', 'items.product']);
        });
    }

    /**
     * Update status of stock opname transaction (e.g. DRAFT -> COMPLETED).
     * When completed, system stock is adjusted to physical stock.
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

            // If changing DRAFT/REVIEW -> COMPLETED: update product current_stock to physical_stock
            if ($oldStatus !== 'COMPLETED' && $newStatus === 'COMPLETED') {
                foreach ($opname->items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->current_stock = $item->physical_stock;
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

            return $opname->fresh()->load(['creator', 'items.product']);
        });
    }

    /**
     * Delete a stock opname record.
     */
    public function deleteStockOpname(StockOpname $opname): bool
    {
        return DB::transaction(function () use ($opname) {
            $oldValues = $opname->load('items')->toArray();
            $code = $opname->opname_code;

            $deleted = $opname->delete();

            ActivityLogService::log(
                action:      'DELETE',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Deleted Stock Opname transaction: {$code}",
                oldValues:   $oldValues,
                newValues:   null
            );

            return $deleted;
        });
    }

    /**
     * Generate unique stock opname code (format: OPN-YYYYMMDD-XXXX).
     */
    private function generateOpnameCode(): string
    {
        $datePrefix = Carbon::now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        $code = "OPN-{$datePrefix}-{$random}";

        while (StockOpname::where('opname_code', $code)->exists()) {
            $random = strtoupper(Str::random(4));
            $code = "OPN-{$datePrefix}-{$random}";
        }

        return $code;
    }
}
