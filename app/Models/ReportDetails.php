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
        'quantity',
        'selling_price',
        'hpp',
        'total_price',
        'total_hpp',
        'margin'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'selling_price' => 'decimal:3',
            'hpp' => 'decimal:3',
            'total_price' => 'decimal:3',
            'total_hpp' => 'decimal:3',
            'margin' => 'decimal:3',
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
}