<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class RestockItems extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'restock_items';

    protected $fillable = [
        'restock_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function restock()
    {
        return $this->belongsTo(Restock::class, 'restock_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}