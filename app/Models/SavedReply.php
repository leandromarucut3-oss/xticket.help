<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    protected $table = 'saved_replies';

    protected $fillable = [
        'text',
        'created_by',
    ];
}
