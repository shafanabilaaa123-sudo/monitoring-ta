<?php

namespace Database\Seeders;

use App\Models\CatatanBimbingan;
use App\Models\JadwalBimbingan;
use App\Models\Notifikasi;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pengaman: akun demo berkata sandi "password", jangan pernah dibuat di server publik.
        if (app()->environment('production')) {
            $this->command->error('Seeder demo dilewati karena APP_ENV=production. Buat admin dengan: php artisan admin:buat email@kamu.com');

            return;
        }

        // Semua akun demo berkata sandi: password
        $admin = $this->akun('Koordinator Prodi', 'admin@kampus.test', 'admin', 'ADM001');
        $dosen1 = $this->akun('Dr. Budi Santoso, M.Kom.', 'dosen1@kampus.test', 'dosen', '198001012005011001');
        $dosen2 = $this->akun('Siti Rahmawati, M.T.', 'dosen2@kampus.test', 'dosen', '198505052010012002');
        $mhs1 = $this->akun('Andi Pratama', 'mhs1@kampus.test', 'mahasiswa', '2201001');
        $mhs2 = $this->akun('Nur Aisyah', 'mhs2@kampus.test', 'mahasiswa', '2201002');
        $this->akun('Rizky Maulana', 'mhs3@kampus.test', 'mahasiswa', '2201003'); // belum mengajukan judul

        // Data contoh: mahasiswa 1 sudah berjalan bersama dosen 1
        $ta1 = TugasAkhir::create([
            'mahasiswa_id' => $mhs1->id,
            'dosen_id' => $dosen1->id,
            'judul' => 'Sistem Informasi Perpustakaan Berbasis Web',
            'deskripsi' => 'Membangun sistem peminjaman dan katalog buku untuk perpustakaan kampus.',
            'tahapan' => 'Bab 1-3',
            'status' => 'berjalan',
            'aktivitas_terakhir' => now()->subDays(2),
        ]);

        $jadwalSelesai = JadwalBimbingan::create([
            'tugas_akhir_id' => $ta1->id,
            'waktu' => now()->subDays(3),
            'topik' => 'Review Bab 1 dan Bab 2',
            'status' => 'selesai',
        ]);

        CatatanBimbingan::create([
            'tugas_akhir_id' => $ta1->id,
            'jadwal_bimbingan_id' => $jadwalSelesai->id,
            'isi' => 'Latar belakang sudah cukup jelas. Rumusan masalah perlu dipertajam.',
            'revisi' => 'Perbaiki rumusan masalah dan tambahkan 3 jurnal terkait pada Bab 2.',
        ]);

        JadwalBimbingan::create([
            'tugas_akhir_id' => $ta1->id,
            'waktu' => now()->addDays(2)->setTime(10, 0),
            'topik' => 'Konsultasi Bab 3 (metodologi)',
            'status' => 'diajukan',
        ]);

        // Mahasiswa 2 sudah mengajukan judul, menunggu pembimbing
        TugasAkhir::create([
            'mahasiswa_id' => $mhs2->id,
            'judul' => 'Aplikasi Monitoring Stok Barang UMKM',
            'deskripsi' => 'Aplikasi pencatatan stok untuk usaha kecil.',
            'tahapan' => 'Pengajuan Judul',
            'status' => 'menunggu_pembimbing',
            'aktivitas_terakhir' => now(),
        ]);

        // Notifikasi contoh agar ikon lonceng terisi saat pertama dibuka
        Notifikasi::create([
            'user_id' => $dosen1->id,
            'jenis' => 'jadwal',
            'pesan' => 'Andi Pratama mengajukan bimbingan (Konsultasi Bab 3 (metodologi)).',
            'tautan' => '/ta/' . $ta1->id,
        ]);
        Notifikasi::create([
            'user_id' => $admin->id,
            'jenis' => 'judul',
            'pesan' => 'Nur Aisyah mengajukan judul TA. Tetapkan dosen pembimbing.',
            'tautan' => '/dashboard',
        ]);
    }

    private function akun(string $nama, string $email, string $peran, string $nomorInduk): User
    {
        return User::create([
            'name' => $nama,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $peran,
            'nomor_induk' => $nomorInduk,
        ]);
    }
}
