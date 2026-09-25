<?php

namespace App\Services;

use App\Models\Hpp;
use App\Models\Products;
use Illuminate\Support\Facades\DB;

class HppService
{
    /**
     * Get all HPP components.
     */
    public function getAllHpp()
    {
        return Hpp::withCount('productHpps')->orderBy('name', 'asc')->get();
    }

    /**
     * Find HPP component by ID.
     */
    public function getHppById(int $id): Hpp
    {
        return Hpp::findOrFail($id);
    }

    /**
     * Create a new HPP component.
     */
    public function createHpp(array $data): Hpp
    {
        return DB::transaction(function () use ($data) {
            $hpp = Hpp::create([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'unit_cost' => $data['unit_cost'],
            ]);

            ActivityLogService::log(
                action: 'CREATE',
                module: 'HPP',
                entityType: Hpp::class,
                entityId: $hpp->id,
                description: "Created HPP component: {$hpp->name}",
                oldValues: null,
                newValues: $hpp->toArray()
            );

            return $hpp;
        });
    }

    /**
     * Update an existing HPP component.
     */
    public function updateHpp(Hpp $hpp, array $data): Hpp
    {
        return DB::transaction(function () use ($hpp, $data) {
            $oldValues = $hpp->toArray();

            $hpp->update([
                'name' => $data['name'] ?? $hpp->name,
                'unit' => $data['unit'] ?? $hpp->unit,
                'unit_cost' => $data['unit_cost'] ?? $hpp->unit_cost,
            ]);

            ActivityLogService::log(
                action: 'UPDATE',
                module: 'HPP',
                entityType: Hpp::class,
                entityId: $hpp->id,
                description: "Updated HPP component: {$hpp->name}",
                oldValues: $oldValues,
                newValues: $hpp->fresh()->toArray()
            );

            return $hpp;
        });
    }

    /**
     * Delete an HPP component.
     */
    public function deleteHpp(Hpp $hpp): bool
    {
        $configsCount = $hpp->productHpps()->count();
        if ($configsCount > 0) {
            throw new \Exception("Gagal menghapus! Komponen HPP '{$hpp->name}' masih digunakan oleh {$configsCount} konfigurasi penjualan produk.");
        }

        return DB::transaction(function () use ($hpp) {
            $oldValues = $hpp->toArray();
            $id = $hpp->id;
            $name = $hpp->name;

            $deleted = $hpp->delete();

            ActivityLogService::log(
                action: 'DELETE',
                module: 'HPP',
                entityType: Hpp::class,
                entityId: $id,
                description: "Deleted HPP component: {$name}",
                oldValues: $oldValues,
                newValues: null
            );

            return $deleted;
        });
    }
}
