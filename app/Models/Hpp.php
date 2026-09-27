<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Hpp extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'hpp';

    protected $fillable = [
        'name',
        'unit',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:3',
        ];
    }

    /**
     * Relasi ke rincian detail komponen HPP.
     */
    public function productHppDetails()
    {
        return $this->hasMany(ProductHppDetail::class, 'hpp_id');
    }

    /**
     * Relasi ke ProductHpp header melalui product_hpp_detail.
     */
    public function productHpps()
    {
        return $this->belongsToMany(ProductHpp::class, 'product_hpp_detail', 'hpp_id', 'product_hpp_id')
            ->withTimestamps();
    }

    /**
     * Relasi ke Products melalui product_hpp dan product_hpp_detail.
     */
    public function products()
    {
        return $this->belongsToMany(Products::class, 'product_hpp_detail', 'hpp_id', 'product_hpp_id')
            ->withTimestamps();
    }
}