<?php

namespace App\Services;

use App\Helpers\CodeGenerator;
use App\Models\Products;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

                $product     = Products::lockForUpdate()->findOrFail($productId);
                $systemStock = (int) $product->current_stock;
                $difference  = $physicalStock - $systemStock;

                StockOpnameItem::create([
                    'opname_id'           => $opname->id,
                    'product_id'          => $productId,
                    'system_stock'        => $systemStock,
                    'physical_stock'      => $physicalStock,
                    'difference'          => $difference,
                    'stock_before_opname' => (int) $product->initial_stock,
                ]);

                // Jika COMPLETED: update initial_stock ke hasil hitung fisik
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
     * Update an existing stock opname transaction (e.g. dari DRAFT).
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
                        'stock_before_opname' => (int) $product->initial_stock,
                    ]);

                    // Jika COMPLETED: update initial_stock ke hasil hitung fisik
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

            // DRAFT/REVIEW → COMPLETED: update initial_stock ke physical_stock
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
     * Rollback a COMPLETED stock opname back to DRAFT.
     *
     * Data opname & items TIDAK dihapus.
     * Yang dilakukan:
     *  1. Restore initial_stock produk ke nilai sebelum opname (stock_before_opname).
     *  2. Ubah status opname kembali ke DRAFT.
     *  3. Catat activity log.
     *
     * Setelah rollback, admin/user bisa membuka kembali form edit dan
     * memperbaiki data sebelum menyelesaikannya lagi.
     */
    public function rollbackStockOpname(StockOpname $opname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($opname, $userId) {
            // Hanya opname berstatus COMPLETED yang bisa di-rollback
            if ($opname->status_opname !== 'COMPLETED') {
                throw new \Exception("Hanya transaksi berstatus COMPLETED yang dapat di-rollback.");
            }

            $opname->load('items');
            $oldValues = $opname->toArray();

            // 1. Restore initial_stock tiap produk ke nilai sebelum opname
            foreach ($opname->items as $item) {
                $product = Products::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->initial_stock = $item->stock_before_opname;
                    $product->save();
                }
            }

            // 2. Kembalikan status ke DRAFT — data tetap ada, bisa diedit kembali
            $opname->update(['status_opname' => 'DRAFT']);

            // 3. Activity log
            ActivityLogService::log(
                action:      'ROLLBACK',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Rolled back Stock Opname {$opname->opname_code} from COMPLETED to DRAFT. Stock restored.",
                oldValues:   $oldValues,
                newValues:   $opname->fresh()->toArray(),
                userId:      $userId
            );

            return $opname->fresh()->load(['creator', 'items.product.unit']);
        });
    }

    /**
     * Hard delete stock opname — HANYA untuk data DRAFT yang ingin dihapus permanen.
     * Tidak boleh dipanggil untuk rollback opname COMPLETED.
     */
    public function deleteStockOpname(StockOpname $opname): bool
    {
        return DB::transaction(function () use ($opname) {
            // Guard: jangan izinkan hard-delete opname yang sudah COMPLETED
            if ($opname->status_opname === 'COMPLETED') {
                throw new \Exception("Transaksi COMPLETED tidak dapat dihapus. Gunakan rollback.");
            }

            $oldValues = $opname->load('items')->toArray();
            $code      = $opname->opname_code;

            $deleted = $opname->delete();

            ActivityLogService::log(
                action:      'DELETE',
                module:      'STOCK_OPNAME',
                entityType:  StockOpname::class,
                entityId:    $opname->id,
                description: "Deleted DRAFT Stock Opname: {$code}",
                oldValues:   $oldValues,
                newValues:   null
            );

            return $deleted;
        });
    }
}