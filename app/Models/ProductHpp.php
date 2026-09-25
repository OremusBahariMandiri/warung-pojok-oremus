<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ProductHpp extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'product_hpp';

    protected $fillable = [
        'product_id',
        'hpp_id',
        'selling_unit_id',
        'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'product_id'      => 'integer',
            'hpp_id'          => 'integer',
            'selling_unit_id' => 'integer',
            'selling_price'   => 'decimal:3',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function hpp()
    {
        return $this->belongsTo(Hpp::class, 'hpp_id');
    }

    public function sellingUnit()
    {
        return $this->belongsTo(Unit::class, 'selling_unit_id');
    }
}