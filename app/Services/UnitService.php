<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitService
{
    /**
     * Get all units with connected products count.
     */
    public function getAllUnits()
    {
        return Unit::withCount('products')->orderBy('unit_name', 'asc')->get();
    }

    /**
     * Find unit by ID.
     */
    public function getUnitById(int $id): Unit
    {
        return Unit::withCount('products')->findOrFail($id);
    }

    /**
     * Create a new unit.
     */
    public function createUnit(array $data): Unit
    {
        return DB::transaction(function () use ($data) {
            $unit = Unit::create([
                'unit_name' => $data['unit_name'],
                'type' => $data['type'],
                'short_name' => $data['short_name'],
            ]);

            ActivityLogService::log(
                action: 'CREATE',
                module: 'UNIT',
                entityType: Unit::class,
                entityId: $unit->id,
                description: "Created unit: {$unit->unit_name} ({$unit->short_name})",
                oldValues: null,
                newValues: $unit->toArray()
            );

            return $unit;
        });
    }

    /**
     * Update an existing unit.
     */
    public function updateUnit(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {
            $oldValues = $unit->toArray();

            $unit->update([
                'unit_name' => $data['unit_name'] ?? $unit->unit_name,
                'type' => $data['type'] ?? $unit->type,
                'short_name' => $data['short_name'] ?? $unit->short_name,
            ]);

            ActivityLogService::log(
                action: 'UPDATE',
                module: 'UNIT',
                entityType: Unit::class,
                entityId: $unit->id,
                description: "Updated unit: {$unit->unit_name}",
                oldValues: $oldValues,
                newValues: $unit->fresh()->toArray()
            );

            return $unit;
        });
    }

    /**
     * Delete a unit. Checks if used by products first.
     */
    public function deleteUnit(Unit $unit): bool
    {
        $productsCount = $unit->products()->count();
        if ($productsCount > 0) {
            throw new \Exception("Gagal menghapus! Satuan '{$unit->unit_name}' masih terhubung ke {$productsCount} produk.");
        }

        return DB::transaction(function () use ($unit) {
            $oldValues = $unit->toArray();
            $id = $unit->id;
            $name = $unit->unit_name;

            $deleted = $unit->delete();

            ActivityLogService::log(
                action: 'DELETE',
                module: 'UNIT',
                entityType: Unit::class,
                entityId: $id,
                description: "Deleted unit: {$name}",
                oldValues: $oldValues,
                newValues: null
            );

            return $deleted;
        });
    }
}
