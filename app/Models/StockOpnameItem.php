<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class StockOpnameItem extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'stock_opname_items';

    protected $fillable = [
        'opname_id',
        'product_id',
        'system_stock',
        'physical_stock',
        'difference',
    ];

    protected function casts(): array
    {
        return [
            'opname_id'      => 'integer',
            'product_id'     => 'integer',
            'system_stock'   => 'integer',
            'physical_stock' => 'integer',
            'difference'     => 'integer',
        ];
    }

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class, 'opname_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
