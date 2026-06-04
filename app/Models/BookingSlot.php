<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'court_id',
        'date',
        'slot',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
