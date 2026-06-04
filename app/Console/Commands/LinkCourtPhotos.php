<?php

namespace App\Console\Commands;

use App\Models\Court;
use Illuminate\Console\Command;

class LinkCourtPhotos extends Command
{
    protected $signature = 'courts:link-photos';

    protected $description = 'Hubungkan foto di public/images ke data lapangan (Lapangan 1.jpg → court id 1)';

    public function handle(): int
    {
        $courts = Court::all();

        if ($courts->isEmpty()) {
            $this->warn('Tidak ada data lapangan di database. Buat lapangan dulu di Admin.');

            return self::FAILURE;
        }

        $linked = 0;

        foreach ($courts as $court) {
            $paths = Court::discoverImagePathsForCourt($court->id);

            if (empty($paths)) {
                $this->line("  - [{$court->id}] {$court->name}: tidak ada file di public/images/");
                continue;
            }

            $court->update(['photos' => $paths]);
            $linked++;
            $this->info("  ✓ [{$court->id}] {$court->name} ← " . implode(', ', $paths));
        }

        $this->newLine();
        $this->info("Selesai. {$linked} lapangan terhubung ke foto.");

        return self::SUCCESS;
    }
}
