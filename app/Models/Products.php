<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Products extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'prod_name',
        'slug',
        'sku',
        'satuan',
        'selling_price',
        'unit_price',
        'hpp_method',
        'current_hpp',
        'current_stock',
        'min_stock',
        'description',
        'thumbnail'
    ];
}