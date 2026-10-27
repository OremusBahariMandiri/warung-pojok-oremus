<?php

namespace App\Services;

use App\Models\OperationalExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class OperationalExpenseService
{
    /**
     * Get all operational expenses with optional date range and search filter.
     */
    public function getExpenses(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = OperationalExpense::query()->orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
        }

        return $query->get();
    }

    /**
     * Store new operational expense.
     */
    public function createExpense(array $data, ?int $userId = null): OperationalExpense
    {
        $quantity = (int)$data['quantity'];
        $price = (float)$data['price'];
        $totalPrice = $quantity * $price;

        $expense = OperationalExpense::create([
            'name'        => $data['name'],
            'quantity'    => $quantity,
            'price'       => $price,
            'total_price' => $totalPrice,
            'date'        => Carbon::parse($data['date']),
            'notes'       => $data['notes'] ?? null,
        ]);

        ActivityLogService::log(
            action: 'CREATE',
            module: 'OPERATIONAL_EXPENSE',
            entityType: OperationalExpense::class,
            entityId: $expense->id,
            description: "Membuat transaksi operasional: {$expense->name} ({$expense->quantity} x Rp " . number_format($expense->price, 0, ',', '.') . " = Rp " . number_format($totalPrice, 0, ',', '.') . ")",
            oldValues: null,
            newValues: $expense->toArray(),
            userId: $userId
        );

        return $expense;
    }

    /**
     * Update existing operational expense.
     */
    public function updateExpense(OperationalExpense $expense, array $data, ?int $userId = null): OperationalExpense
    {
        $oldValues = $expense->toArray();

        $quantity = (int)$data['quantity'];
        $price = (float)$data['price'];
        $totalPrice = $quantity * $price;

        $expense->update([
            'name'        => $data['name'],
            'quantity'    => $quantity,
            'price'       => $price,
            'total_price' => $totalPrice,
            'date'        => Carbon::parse($data['date']),
            'notes'       => $data['notes'] ?? null,
        ]);

        ActivityLogService::log(
            action: 'UPDATE',
            module: 'OPERATIONAL_EXPENSE',
            entityType: OperationalExpense::class,
            entityId: $expense->id,
            description: "Memperbarui transaksi operasional ID {$expense->id}: {$expense->name}",
            oldValues: $oldValues,
            newValues: $expense->fresh()->toArray(),
            userId: $userId
        );

        return $expense->fresh();
    }

    /**
     * Delete operational expense.
     */
    public function deleteExpense(OperationalExpense $expense, ?int $userId = null): bool
    {
        $oldValues = $expense->toArray();
        $id = $expense->id;
        $name = $expense->name;

        $deleted = $expense->delete();

        ActivityLogService::log(
            action: 'DELETE',
            module: 'OPERATIONAL_EXPENSE',
            entityType: OperationalExpense::class,
            entityId: $id,
            description: "Menghapus transaksi operasional ID {$id}: {$name}",
            oldValues: $oldValues,
            newValues: null,
            userId: $userId
        );

        return $deleted;
    }
}
