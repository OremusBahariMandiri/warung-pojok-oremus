<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    use HasFactory;

    protected $table = 'operational_expense';

    protected $fillable = [
        'name',
        'quantity',
        'price',
        'total_price',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'price' => 'float',
        'total_price' => 'float',
    ];

    /**
     * Boot model to automatically compute total_price if not set.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($expense) {
            if ($expense->quantity !== null && $expense->price !== null) {
                $expense->total_price = (float)$expense->quantity * (float)$expense->price;
            }
        });
    }
}
