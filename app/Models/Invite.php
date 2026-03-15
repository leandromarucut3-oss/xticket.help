<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    protected $table = 'invites';

    protected $fillable = [
        'token',
        'username',
        'created_by',
        'used_at',
        'expires_at',
    ];

    protected $dates = [
        'used_at',
        'expires_at',
    ];
}
