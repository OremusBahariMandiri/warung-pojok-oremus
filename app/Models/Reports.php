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
        'total_gross_margin',
        'total_net_margin',
        'comission_type_presentance',
        'comission_type_nominal',
        'commission_value',
        'profit_share_amount',
        'owner_share_amount',
        'report_date',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'created_by'                 => 'integer',
            'total_quantity'             => 'integer',
            'total_sales'                => 'decimal:3',
            'total_hpp'                  => 'decimal:3',
            'total_gross_margin'         => 'decimal:3',
            'total_net_margin'           => 'decimal:3',
            'comission_type_presentance' => 'decimal:3',
            'comission_type_nominal'     => 'decimal:3',
            'commission_value'           => 'decimal:3',
            'profit_share_amount'        => 'decimal:3',
            'owner_share_amount'         => 'decimal:3',
            'report_date'                => 'datetime',
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