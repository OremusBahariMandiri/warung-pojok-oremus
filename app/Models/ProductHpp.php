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
        'selling_unit_id',
        'selling_price',
        'hpp_method',
        'current_hpp',
    ];

    protected function casts(): array
    {
        return [
            'product_id'      => 'integer',
            'selling_unit_id' => 'integer',
            'selling_price'   => 'decimal:3',
            'current_hpp'     => 'decimal:3',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function sellingUnit()
    {
        return $this->belongsTo(Unit::class, 'selling_unit_id');
    }

    /**
     * Relasi ke rincian komponen HPP.
     */
    public function details()
    {
        return $this->hasMany(ProductHppDetail::class, 'product_hpp_id');
    }

    /**
     * Relasi many-to-many ke master HPP melalui product_hpp_detail.
     */
    public function hpps()
    {
        return $this->belongsToMany(Hpp::class, 'product_hpp_detail', 'product_hpp_id', 'hpp_id')
            ->withTimestamps();
    }
}