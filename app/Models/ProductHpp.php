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
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:3',
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
}