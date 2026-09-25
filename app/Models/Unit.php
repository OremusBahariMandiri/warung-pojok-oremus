<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'unit_name',
        'type',
        'short_name',
    ];

    /**
     * Relationship to products that use this unit.
     */
    public function products()
    {
        return $this->hasMany(Products::class, 'unit_id');
    }

    public function productHpps()
    {
        return $this->hasMany(ProductHpp::class, 'selling_unit_id');
    }

    public function restockItems()
    {
        return $this->hasMany(RestockItems::class, 'restock_unit_id');
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetails::class, 'selling_unit_id');
    }
}
