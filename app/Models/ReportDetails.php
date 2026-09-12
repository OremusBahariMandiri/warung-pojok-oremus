<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ReportDetails extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

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
}