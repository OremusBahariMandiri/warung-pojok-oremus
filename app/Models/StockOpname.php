<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class StockOpname extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'stock_opname';

    protected $fillable = [
        'created_by',
        'opname_code',
        'opname_date',
        'notes',
        'status_opname',
    ];

    protected function casts(): array
    {
        return [
            'created_by'  => 'integer',
            'opname_date' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'opname_id');
    }
}
