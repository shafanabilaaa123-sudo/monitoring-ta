<?php

namespace App\Console\Commands;

use App\Services\CadanganData;
use Illuminate\Console\Command;

class EksporData extends Command
{
    protected $signature = 'data:ekspor {berkas? : Lokasi berkas hasil (bawaan: storage/app/cadangan-TANGGAL.json)}';

    protected $description = 'Simpan salinan seluruh data aplikasi ke satu berkas JSON';

    public function handle(CadanganData $cadangan): int
    {
        $berkas = (string) ($this->argument('berkas') ?: storage_path('app/cadangan-' . now()->format('Ymd-His') . '.json'));

        file_put_contents($berkas, $cadangan->keJson());

        $this->info('Salinan data disimpan di: ' . $berkas);

        return self::SUCCESS;
    }
}
