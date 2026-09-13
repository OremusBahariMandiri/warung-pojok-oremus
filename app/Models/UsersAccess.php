<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UsersAccess extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'users_access';

    protected $fillable = [
        'user_id',
        'menu_access',
        'index_acs',
        'show_acs',
        'create_acs',
        'edit_acs',
        'delete_acs'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}