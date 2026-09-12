<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Restock extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'created_by',
        'restock_code',
        'restock_date',
        'supplier_name',
        'notes'
    ];
}