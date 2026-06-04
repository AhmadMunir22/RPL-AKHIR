<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Court;
use App\Models\LoyaltyPoint;
use App\Models\Review;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Users ──
        $admin = User::updateOrCreate(
            ['email' => 'admin@padelbook.com'],
            [
                'name'              => 'Super Admin',
                'phone'             => null,
                'password'          => Hash::make('password'),
                'role'              => 'super_admin',
                'points'            => 0,
                'wallet_balance'    => 0,
                'email_verified_at' => now(),
            ]
        );

        $courtsData = [
            [
                'id' => 1,
                'name' => 'Lapangan 1',
                'type' => 'Indoor',
                'description' => 'Lapangan padel indoor premium dengan pencahayaan LED standar turnamen dan karpet WPT.',
                'price_per_hour' => 15000,
                'photos' => ['images/Lapangan 1.jpg'],
                'status' => 'active',
                'rating_avg' => 4.8
            ],
            [
                'id' => 2,
                'name' => 'Lapangan 2',
                'type' => 'Indoor',
                'description' => 'Lapangan padel indoor premium dengan kenyamanan maksimal dan fasilitas lengkap.',
                'price_per_hour' => 15000,
                'photos' => ['images/Lapangan 2.jpg'],
                'status' => 'active',
                'rating_avg' => 4.6
            ],
            [
                'id' => 3,
                'name' => 'Lapangan 3',
                'type' => 'Outdoor',
                'description' => 'Lapangan padel outdoor dengan pemandangan terbuka dan angin alami yang segar.',
                'price_per_hour' => 10000,
                'photos' => ['images/Lapangan 3.jpg'],
                'status' => 'active',
                'rating_avg' => 4.7
            ],
            [
                'id' => 4,
                'name' => 'Lapangan 4',
                'type' => 'Outdoor',
                'description' => 'Lapangan padel outdoor dengan harga terjangkau untuk latihan santai.',
                'price_per_hour' => 10000,
                'photos' => ['images/Lapangan 4.jpg'],
                'status' => 'active',
                'rating_avg' => 4.5
            ],
            [
                'id' => 5,
                'name' => 'Lapangan 5',
                'type' => 'Indoor',
                'description' => 'Lapangan padel indoor eksklusif dengan tribun penonton kecil di samping lapangan.',
                'price_per_hour' => 15000,
                'photos' => ['images/Lapangan 5.jpg'],
                'status' => 'active',
                'rating_avg' => 4.9
            ]
        ];

        foreach ($courtsData as $data) {
            Court::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('✅ Database telah dikosongkan!');
        $this->command->info('✅ Akun Admin bawaan berhasil dibuat.');
        $this->command->info('✅ 5 Lapangan bawaan dengan foto berhasil dibuat.');
        $this->command->info('');
        $this->command->info('📋 Kredensial Login Admin:');
        $this->command->info('  Email    → admin@padelbook.com');
        $this->command->info('  Password → password');
    }
}
