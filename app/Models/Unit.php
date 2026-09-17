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
}
