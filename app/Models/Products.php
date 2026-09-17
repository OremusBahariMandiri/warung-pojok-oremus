<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Products extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'products';

    protected $fillable = [
        'unit_id',
        'prod_name',
        'slug',
        'sku',
        'selling_price',
        'unit_price',
        'hpp_method',
        'current_hpp',
        'current_stock',
        'min_stock',
        'description',
        'thumbnail'
    ];

    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'selling_price' => 'decimal:3',
            'unit_price' => 'decimal:3',
            'current_hpp' => 'decimal:3',
            'current_stock' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function productHpps()
    {
        return $this->hasMany(ProductHpp::class, 'product_id');
    }

    public function hpps()
    {
        return $this->belongsToMany(Hpp::class, 'product_hpp', 'product_id', 'hpp_id')
            ->withPivot('cost')
            ->withTimestamps();
    }

    public function restockItems()
    {
        return $this->hasMany(RestockItems::class, 'product_id');
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetails::class, 'product_id');
    }
}