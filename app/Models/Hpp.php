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

    public function productHpps()
    {
        return $this->hasMany(ProductHpp::class, 'hpp_id');
    }

    public function products()
    {
        return $this->belongsToMany(Products::class, 'product_hpp', 'hpp_id', 'product_id')
            ->withPivot('cost')
            ->withTimestamps();
    }
}