<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Court extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'price_per_hour',
        'photos',
        'status',
        'rating_avg',
    ];

    protected $casts = [
        'photos'        => 'array',
        'price_per_hour'=> 'float',
        'rating_avg'    => 'float',
    ];

    // ── Relationships ──

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Booking::class,
            'court_id',  // FK on bookings
            'booking_id' // FK on reviews
        );
    }

    public function blockedSlots()
    {
        return $this->hasMany(BlockedSlot::class);
    }

    // ── Helpers ──

    public function getPhotosAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Foto untuk ditampilkan: dari database, atau fallback public/images/Lapangan {id}.jpg
     *
     * @return array<int, string>
     */
    public function getDisplayPhotosAttribute(): array
    {
        $stored = $this->photos;
        if (!empty($stored)) {
            return array_values(array_filter(array_map(
                fn (string $path) => self::toPublicPhotoUrl($path),
                $stored
            )));
        }

        return $this->discoverPhotosFromPublicFolder();
    }

    public function getPrimaryPhotoAttribute(): ?string
    {
        $photos = $this->display_photos;

        return $photos[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function discoverImagePathsForCourt(int $courtId): array
    {
        $dir = public_path('images');
        if (!is_dir($dir)) {
            return [];
        }

        $candidates = [
            "Lapangan {$courtId}.jpg",
            "Lapangan {$courtId}.jpeg",
            "Lapangan {$courtId}.png",
            "Lapangan {$courtId}.webp",
            "lapangan-{$courtId}.jpg",
            "lapangan_{$courtId}.jpg",
        ];

        $paths = [];
        foreach ($candidates as $filename) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
                $paths[] = 'images/' . $filename;
            }
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    protected function discoverPhotosFromPublicFolder(): array
    {
        if (!$this->id) {
            return [];
        }

        return array_map(
            fn (string $path) => self::toPublicPhotoUrl($path),
            self::discoverImagePathsForCourt($this->id)
        );
    }

    public static function toPublicPhotoUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'images/')) {
            $filename = substr($path, strlen('images/'));

            return asset('images/' . rawurlencode($filename));
        }

        return asset($path);
    }

    public function updateRating(): void
    {
        $avg = $this->reviews()->avg('rating') ?? 0;
        $this->update(['rating_avg' => round($avg, 1)]);
    }
}
