<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ReportDetails extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'report_details';

    protected $fillable = [
        'report_id',
        'product_id',
        'selling_unit_id',
        'quantity',
        'stock_final',
        'selling_price',
        'hpp_unit',
        'subtotal_price',
        'subtotal_hpp',
        'subtotal_margin',
    ];

    protected function casts(): array
    {
        return [
            'report_id'       => 'integer',
            'product_id'      => 'integer',
            'selling_unit_id' => 'integer',
            'quantity'        => 'integer',
            'stock_final'     => 'integer',
            'selling_price'   => 'decimal:3',
            'hpp_unit'        => 'decimal:3',
            'subtotal_price'  => 'decimal:3',
            'subtotal_hpp'    => 'decimal:3',
            'subtotal_margin' => 'decimal:3',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Reports::class, 'report_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function sellingUnit()
    {
        return $this->belongsTo(Unit::class, 'selling_unit_id');
    }
}