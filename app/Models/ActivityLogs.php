<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ActivityLogs extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'ip_address'
    ];
}