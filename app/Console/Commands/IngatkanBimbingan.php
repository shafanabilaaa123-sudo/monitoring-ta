<?php

namespace App\Console\Commands;

use App\Models\JadwalBimbingan;
use App\Models\Notifikasi;
use App\Models\TugasAkhir;
use App\Services\Notifier;
use Illuminate\Console\Command;

class IngatkanBimbingan extends Command
{
    protected $signature = 'bimbingan:ingatkan';

    protected $description = 'Kirim pengingat bimbingan (24 jam ke depan) dan peringatan mahasiswa tanpa aktivitas 14 hari';

    public function handle(): int
    {
        // 1. Pengingat jadwal yang disetujui dan akan berlangsung dalam 24 jam
        $jadwal = JadwalBimbingan::with('tugasAkhir.mahasiswa')
            ->where('status', 'disetujui')
            ->where('diingatkan', false)
            ->whereBetween('waktu', [now(), now()->addDay()])
            ->get();

        foreach ($jadwal as $j) {
            $ta = $j->tugasAkhir;
            $tautan = route('ta.detail', $ta, false);
            $waktu = $j->waktu->translatedFormat('d F Y H:i');

            Notifier::kirim($ta->mahasiswa_id, 'pengingat', 'Pengingat: bimbingan "' . $j->topik . '" pada ' . $waktu . '.', $tautan);
            Notifier::kirim($ta->dosen_id, 'pengingat', 'Pengingat: bimbingan dengan ' . $ta->mahasiswa->name . ' pada ' . $waktu . '.', $tautan);

            $j->update(['diingatkan' => true]);
        }

        // 2. Peringatan TA tanpa aktivitas 14 hari (maksimal sekali per 7 hari)
        $tidakAktif = TugasAkhir::with('mahasiswa')
            ->where('status', 'berjalan')
            ->where(function ($q) {
                $q->whereNull('aktivitas_terakhir')
                    ->orWhere('aktivitas_terakhir', '<', now()->subDays(14));
            })
            ->get();

        $jumlahPeringatan = 0;
        foreach ($tidakAktif as $ta) {
            $sudah = Notifikasi::where('user_id', $ta->mahasiswa_id)
                ->where('jenis', 'tidak_aktif')
                ->where('created_at', '>=', now()->subDays(7))
                ->exists();

            if ($sudah) {
                continue;
            }

            $tautan = route('ta.detail', $ta, false);
            Notifier::kirim($ta->mahasiswa_id, 'tidak_aktif', 'Sudah 14 hari tanpa aktivitas bimbingan. Segera ajukan jadwal bimbingan.', $tautan);
            Notifier::kirim($ta->dosen_id, 'tidak_aktif', $ta->mahasiswa->name . ' belum ada aktivitas bimbingan selama 14 hari.', $tautan);
            $jumlahPeringatan++;
        }

        $this->info('Pengingat jadwal: ' . $jadwal->count() . ', peringatan tidak aktif: ' . $jumlahPeringatan);

        return self::SUCCESS;
    }
}
