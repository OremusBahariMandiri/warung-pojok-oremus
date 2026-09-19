<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Reports extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'reports';

    protected $fillable = [
        'created_by',
        'total_quantity',
        'total_sales',
        'total_hpp',
        'total_margin',
        'report_date',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'total_quantity' => 'integer',
            'total_sales' => 'decimal:3',
            'total_hpp' => 'decimal:3',
            'total_margin' => 'decimal:3',
            'report_date' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(ReportDetails::class, 'report_id');
    }
}