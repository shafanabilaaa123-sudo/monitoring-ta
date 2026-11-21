<?php

namespace App\Console\Commands;

use App\Services\CadanganData;
use Illuminate\Console\Command;

class ImporData extends Command
{
    protected $signature = 'data:impor {berkas : Berkas JSON hasil data:ekspor} {--ya : Lewati pertanyaan konfirmasi}';

    protected $description = 'Pulihkan data dari berkas cadangan (MENGGANTI semua data sekarang)';

    public function handle(CadanganData $cadangan): int
    {
        $berkas = (string) $this->argument('berkas');

        if (! is_file($berkas)) {
            $this->error('Berkas tidak ditemukan: ' . $berkas);

            return self::FAILURE;
        }

        $isi = json_decode((string) file_get_contents($berkas), true);
        if (! is_array($isi)) {
            $this->error('Isi berkas bukan JSON yang valid.');

            return self::FAILURE;
        }

        if (! $this->option('ya') && ! $this->confirm('Semua data yang ada sekarang akan DIGANTI dengan isi cadangan. Lanjutkan?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        try {
            $hasil = $cadangan->impor($isi);
        } catch (\Throwable $e) {
            $this->error('Pemulihan gagal, data lama tidak berubah: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($hasil as $tabel => $jumlah) {
            $this->line(str_pad($tabel, 22) . $jumlah . ' baris');
        }
        $this->info('Pemulihan selesai.');

        return self::SUCCESS;
    }
}
