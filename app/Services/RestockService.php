<?php

namespace App\Services;

use App\Models\Products;
use App\Models\Restock;
use App\Models\RestockItems;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
     */
    public function createRestock(array $data, int $userId): Restock
    {
        return DB::transaction(function () use ($data, $userId) {
            $restockCode = !empty($data['restock_code'])
                ? $data['restock_code']
                : \App\Helpers\CodeGenerator::generateRestockCode(Restock::class);
            $restockDate = !empty($data['restock_date']) ? Carbon::parse($data['restock_date']) : Carbon::now();
            $status      = strtoupper($data['status_restock'] ?? 'DRAFT');

            $restock = Restock::create([
                'created_by'     => $userId,
                'restock_code'   => $restockCode,
                'restock_date'   => $restockDate,
                'supplier_name'  => $data['supplier_name'] ?? 'Supplier Umum',
                'status_restock' => $status,
                'notes'          => $data['notes'] ?? '',
            ]);

            foreach ($data['items'] as $itemData) {
                $productId     = (int) $itemData['product_id'];
                // $product       = Products::find($productId);
                // $restockUnitId = !empty($itemData['restock_unit_id'])
                //     ? (int) $itemData['restock_unit_id']
                //     : ($product ? $product->unit_id : 1);
                $quantity      = (int) $itemData['quantity'];
                $purchasePrice = (float) $itemData['purchase_price'];
                $itemTotal     = $quantity * $purchasePrice;

                $previousUnitPrice = null;

                if ($status === 'CONFIRMED') {
                    $prod = Products::lockForUpdate()->find($productId);
                    if ($prod) {
                        $previousUnitPrice   = (float) $prod->unit_price;
                        $prod->current_stock = $prod->current_stock + $quantity;
                        $prod->unit_price    = $purchasePrice;
                        $prod->save();
                    }
                }

                RestockItems::create([
                    'restock_id'          => $restock->id,
                    'product_id'          => $productId,
                    // 'restock_unit_id'     => $restockUnitId,
                    'quantity'            => $quantity,
                    'purchase_price'      => $purchasePrice,
                    'total_price'         => $itemTotal,
                    'previous_unit_price' => $previousUnitPrice,
                ]);
            }

            ActivityLogService::log(
                action:      'CREATE',
                module:      'RESTOCK',
                entityType:  Restock::class,
                entityId:    $restock->id,
                description: "Created Restock {$restock->restock_code} (Status: {$status})",
                oldValues:   null,
                newValues:   $restock->load(['creator', 'items.product', 'items.unit'])->toArray(),
                userId:      $userId
            );

            return $restock->load(['creator', 'items.product', 'items.unit']);
        });
    }

    /**
     * Update an existing restock transaction.
     *
     * ═══════════════════════════════════════════════════════════════════════
     * ALUR LOGIKA:
     *
     * STEP 1 — Baca items LAMA dari DB (fresh query, bukan Eloquent cache)
     *
     * STEP 2 — Jika oldStatus = CONFIRMED:
     *          Rollback efek ke produk SEBELUM items dihapus:
     *          a. current_stock dikurangi qty lama
     *          b. unit_price di-restore ke previous_unit_price lama
     *
     * STEP 3 — Update header restock
     *
     * STEP 4 — Hapus items lama dari DB
     *
     * STEP 5 — Tentukan sumber items baru:
     *          - Jika ada items dari request (form DRAFT yang diedit) → pakai itu
     *          - Jika tidak ada items dari request (form CONFIRMED yang locked,
     *            hanya bisa rollback, TIDAK ada path edit dari CONFIRMED) →
     *            ini seharusnya tidak terjadi dari UI, tapi untuk robustness
     *            fallback ke items lama yang sudah dibaca di STEP 1
     *
     * STEP 6 — Insert items baru & apply efek ke produk jika newStatus = CONFIRMED:
     *          a. Capture previous_unit_price dari unit_price produk SAAT INI
     *             (sudah di-restore di STEP 2 jika sebelumnya CONFIRMED)
     *          b. current_stock ditambah qty baru
     *          c. unit_price ditimpa dengan purchase_price baru
     * ═══════════════════════════════════════════════════════════════════════
     */
    public function updateRestock(Restock $restock, array $data, int $userId): Restock
    {
        return DB::transaction(function () use ($restock, $data, $userId) {
            $oldStatus = $restock->status_restock;
            $newStatus = strtoupper($data['status_restock'] ?? $oldStatus);

            // ── STEP 1: Baca items LAMA langsung dari DB ──────────────────────────
            // Pakai fresh query — jangan pakai $restock->items yang bisa ter-cache.
            $oldItems  = RestockItems::where('restock_id', $restock->id)->get();
            $oldValues = $restock->load(['creator', 'items.product', 'items.unit'])->toArray();

            // ── STEP 2: Rollback efek ke produk jika oldStatus = CONFIRMED ────────
            if ($oldStatus === 'CONFIRMED') {
                foreach ($oldItems as $oldItem) {
                    $prod = Products::lockForUpdate()->find($oldItem->product_id);
                    if (!$prod) continue;

                    // a. Kembalikan stok
                    $prod->current_stock = max(0, $prod->current_stock - (int) $oldItem->quantity);

                    // b. Kembalikan unit_price ke snapshot sebelum restock ini
                    $prevPrice = $oldItem->previous_unit_price;
                    if (!is_null($prevPrice)) {
                        $prod->unit_price = (float) $prevPrice;
                    }

                    $prod->save();
                }
            }

            // ── STEP 3: Update header restock ─────────────────────────────────────
            $restockDate = !empty($data['restock_date'])
                ? Carbon::parse($data['restock_date'])
                : $restock->restock_date;

            $restock->update([
                'supplier_name'  => $data['supplier_name'] ?? $restock->supplier_name,
                'restock_date'   => $restockDate,
                'status_restock' => $newStatus,
                'notes'          => $data['notes'] ?? $restock->notes,
            ]);

            // ── STEP 4: Hapus items lama ──────────────────────────────────────────
            RestockItems::where('restock_id', $restock->id)->delete();

            // ── STEP 5: Tentukan sumber items baru ───────────────────────────────
            // Cek apakah request mengirim items yang valid (ada dan product_id-nya
            // tidak kosong). Ini terjadi saat form DRAFT diedit dan disubmit.
            $requestItems = (!empty($data['items']) && is_array($data['items']))
                ? array_values(array_filter($data['items'], function ($item) {
                    return !empty($item['product_id']) && (int) $item['product_id'] > 0;
                }))
                : [];

            if (!empty($requestItems)) {
                // ── Sumber: items dari request (form DRAFT yang diedit) ──
                $newItemsData = array_map(function ($itemData) {
                    $productId     = (int) $itemData['product_id'];
                    // $product       = Products::find($productId);
                    // $restockUnitId = !empty($itemData['restock_unit_id'])
                    //     ? (int) $itemData['restock_unit_id']
                    //     : ($product ? $product->unit_id : 1);
                    $quantity      = (int) $itemData['quantity'];
                    $purchasePrice = (float) $itemData['purchase_price'];

                    return [
                        'product_id'      => $productId,
                        // 'restock_unit_id' => $restockUnitId,
                        'quantity'        => $quantity,
                        'purchase_price'  => $purchasePrice,
                        'total_price'     => $quantity * $purchasePrice,
                    ];
                }, $requestItems);
            } else {
                // ── Fallback: pakai items lama dari DB ──
                // Ini terjadi jika form dikirim tanpa items (misal form CONFIRMED
                // yang locked — seharusnya tidak terjadi dari UI karena form CONFIRMED
                // hanya punya tombol Rollback, bukan Simpan. Tapi handle untuk safety.)
                $newItemsData = $oldItems->map(function ($oldItem) {
                    return [
                        'product_id'      => $oldItem->product_id,
                        // 'restock_unit_id' => $oldItem->restock_unit_id,
                        'quantity'        => (int) $oldItem->quantity,
                        'purchase_price'  => (float) $oldItem->purchase_price,
                        'total_price'     => (float) $oldItem->total_price,
                    ];
                })->toArray();
            }

            // ── STEP 6: Insert items baru & apply efek ke produk jika CONFIRMED ──
            foreach ($newItemsData as $itemData) {
                $previousUnitPrice = null;

                if ($newStatus === 'CONFIRMED') {
                    // Re-fetch dengan lock — nilai stok & harga sudah benar
                    // (di-restore di STEP 2 jika sebelumnya CONFIRMED)
                    $prod = Products::lockForUpdate()->find($itemData['product_id']);
                    if ($prod) {
                        // Capture unit_price SAAT INI sebelum ditimpa
                        $previousUnitPrice   = (float) $prod->unit_price;
                        $prod->current_stock = $prod->current_stock + $itemData['quantity'];
                        $prod->unit_price    = $itemData['purchase_price'];
                        $prod->save();
                    }
                }

                RestockItems::create([
                    'restock_id'          => $restock->id,
                    'product_id'          => $itemData['product_id'],
                    // 'restock_unit_id'     => $itemData['restock_unit_id'],
                    'quantity'            => $itemData['quantity'],
                    'purchase_price'      => $itemData['purchase_price'],
                    'total_price'         => $itemData['total_price'],
                    'previous_unit_price' => $previousUnitPrice,
                ]);
            }

            ActivityLogService::log(
                action:      'UPDATE',
                module:      'RESTOCK',
                entityType:  Restock::class,
                entityId:    $restock->id,
                description: "Updated Restock {$restock->restock_code} (Status: {$newStatus})",
                oldValues:   $oldValues,
                newValues:   $restock->fresh()->load(['creator', 'items.product', 'items.unit'])->toArray(),
                userId:      $userId
            );

            return $restock->fresh()->load(['creator', 'items.product', 'items.unit']);
        });
    }

    /**
     * Update status of restock transaction (e.g. from DRAFT to CONFIRMED).
     * Dipakai oleh route PATCH /restock/{id}/status — bukan dari form edit.
     */
    public function updateStatus(Restock $restock, string $newStatus, int $userId): Restock
    {
        return DB::transaction(function () use ($restock, $newStatus, $userId) {
            $oldStatus = $restock->status_restock;
            $newStatus = strtoupper($newStatus);

            if ($oldStatus === $newStatus) {
                return $restock;
            }

            $items = RestockItems::where('restock_id', $restock->id)->get();

            if ($oldStatus === 'DRAFT' && $newStatus === 'CONFIRMED') {
                foreach ($items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $item->previous_unit_price = (float) $product->unit_price;
                        $item->save();

                        $product->current_stock = $product->current_stock + (int) $item->quantity;
                        $product->unit_price    = (float) $item->purchase_price;
                        $product->save();
                    }
                }
            } elseif ($oldStatus === 'CONFIRMED' && $newStatus === 'DRAFT') {
                foreach ($items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->current_stock = max(0, $product->current_stock - (int) $item->quantity);

                        $prevPrice = $item->previous_unit_price;
                        if (!is_null($prevPrice)) {
                            $product->unit_price = (float) $prevPrice;
                        }

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
     * Rollback restock transaction (DELETE /restock/{id}):
     *   - Kurangi current_stock produk sebesar qty restock
     *   - Kembalikan unit_price ke previous_unit_price
     *   - Ubah status ke DRAFT, data TIDAK dihapus
     */
    public function deleteRestock(Restock $restock): bool
    {
        return DB::transaction(function () use ($restock) {
            $items     = RestockItems::where('restock_id', $restock->id)->get();
            $oldValues = $restock->toArray();
            $code      = $restock->restock_code;

            if ($restock->status_restock === 'CONFIRMED') {
                foreach ($items as $item) {
                    $product = Products::lockForUpdate()->find($item->product_id);
                    if (!$product) continue;

                    // Kembalikan stok
                    $product->current_stock = max(0, $product->current_stock - (int) $item->quantity);

                    // Kembalikan unit_price ke snapshot sebelum restock ini dikonfirmasi
                    $prevPrice = $item->previous_unit_price;
                    if (!is_null($prevPrice)) {
                        $product->unit_price = (float) $prevPrice;
                    }

                    $product->save();
                }
            }

            // Ubah status ke DRAFT — data TIDAK dihapus
            $restock->update(['status_restock' => 'DRAFT']);

            ActivityLogService::log(
                action:      'ROLLBACK',
                module:      'RESTOCK',
                entityType:  Restock::class,
                entityId:    $restock->id,
                description: "Rolled back Restock {$code}: CONFIRMED → DRAFT",
                oldValues:   $oldValues,
                newValues:   $restock->fresh()->toArray()
            );

            return true;
        });
    }
}