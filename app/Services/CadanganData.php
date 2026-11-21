<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Salinan (cadangan) seluruh data aplikasi ke satu berkas JSON, dan pemulihannya.
 * Berlaku untuk MySQL, PostgreSQL, maupun SQLite.
 *
 * Catatan: berkas dokumen yang diunggah mahasiswa TIDAK ikut (hanya datanya di database).
 * Kata sandi tersimpan dalam bentuk hash, tapi berkas cadangan tetap harus dijaga kerahasiaannya.
 */
class CadanganData
{
    /** Urutan penting: tabel induk dulu, tabel yang bergantung kemudian. */
    public const TABEL = [
        'users',
        'tugas_akhir',
        'jadwal_bimbingan',
        'catatan_bimbingan',
        'dokumen',
        'notifikasi',
        'push_subscriptions',
    ];

    public function ekspor(): array
    {
        $tabel = [];
        foreach (self::TABEL as $nama) {
            $tabel[$nama] = DB::table($nama)->orderBy('id')->get()
                ->map(fn ($baris) => (array) $baris)->all();
        }

        return [
            'aplikasi' => 'monitoring-ta',
            'versi' => 1,
            'dibuat' => now()->toIso8601String(),
            'tabel' => $tabel,
        ];
    }

    public function keJson(): string
    {
        return json_encode($this->ekspor(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Mengganti SEMUA data sekarang dengan isi cadangan. Semuanya berhasil atau tidak sama sekali.
     *
     * @return array<string,int> jumlah baris yang dipulihkan per tabel
     */
    public function impor(array $isi): array
    {
        if (($isi['aplikasi'] ?? null) !== 'monitoring-ta' || ! isset($isi['tabel']) || ! is_array($isi['tabel'])) {
            throw new \InvalidArgumentException('Berkas ini bukan cadangan Monitoring TA.');
        }

        $hasil = [];

        DB::transaction(function () use ($isi, &$hasil) {
            foreach (array_reverse(self::TABEL) as $nama) {
                DB::table($nama)->delete();
            }

            foreach (self::TABEL as $nama) {
                $baris = $isi['tabel'][$nama] ?? [];
                foreach (array_chunk($baris, 200) as $bagian) {
                    DB::table($nama)->insert($bagian);
                }
                $hasil[$nama] = count($baris);

                if (DB::getDriverName() === 'pgsql') {
                    // Setelah memasukkan id sendiri, nomor urut berikutnya harus menyusul
                    DB::statement(
                        "SELECT setval(pg_get_serial_sequence('{$nama}', 'id'), "
                        . "COALESCE((SELECT MAX(id) FROM {$nama}), 1), (SELECT COUNT(*) > 0 FROM {$nama}))"
                    );
                }
            }
        });

        return $hasil;
    }
}
