<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_booking',
        'quota',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'value' => 'float',
        'min_booking' => 'float',
        'quota' => 'integer',
    ];

    public function isValidFor(float $bookingPrice): bool
    {
        if ($this->quota <= 0) {
            return false;
        }

        if ($this->expired_at->isPast()) {
            return false;
        }

        if ($bookingPrice < $this->min_booking) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $bookingPrice, float $hourlyPrice = 0): float
    {
        if ($this->type === 'free_hour') {
            return min($bookingPrice, $hourlyPrice);
        }

        if ($this->type === 'percentage') {
            return min($bookingPrice, ($bookingPrice * $this->value) / 100);
        }

        return min($bookingPrice, $this->value);
    }
}
