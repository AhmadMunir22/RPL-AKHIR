<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'method',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
