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
        'prod_code',
        'prod_name',
        'slug',
        'hpp_method',
        'initial_stock',
        'current_stock',
        'min_stock',
        'description',
        'thumbnail'
    ];

    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'current_stock' => 'integer',
            'min_stock' => 'integer',
            'initial_stock' => 'integer',
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