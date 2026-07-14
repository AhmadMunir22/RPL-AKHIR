<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'test:email {to?}';
    protected $description = 'Test kirim email konfigurasi SMTP';

    public function handle(): int
    {
        $to = $this->argument('to') ?? config('mail.from.address');
        $this->info("Mengirim test email ke: {$to}...");

        try {
            Mail::raw(
                "Halo!\n\nIni adalah email uji coba dari sistem PadelBook.\n\nJika Anda menerima email ini, berarti konfigurasi SMTP sudah berfungsi dengan benar!\n\nSalam,\nTim PadelBook",
                function ($message) use ($to) {
                    $message->to($to)->subject('[PadelBook] ✅ Test Konfigurasi Email Berhasil!');
                }
            );
            $this->info('✅ Email berhasil dikirim!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Email GAGAL dikirim: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
