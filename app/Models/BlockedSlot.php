<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'date',
        'slots',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'slots' => 'array',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }
}
