<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Restock extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'restock';

    protected $fillable = [
        'created_by',
        'restock_code',
        'restock_date',
        'supplier_name',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'restock_date' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(RestockItems::class, 'restock_id');
    }
}