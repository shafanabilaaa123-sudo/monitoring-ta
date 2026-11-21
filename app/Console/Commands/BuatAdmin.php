<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BuatAdmin extends Command
{
    protected $signature = 'admin:buat
        {email : Email admin}
        {--nama=Admin : Nama admin}
        {--sandi= : Kata sandi (untuk otomatisasi). Kalau kosong, akan ditanyakan.}';

    protected $description = 'Buat akun admin pertama (dipakai di server produksi, tanpa data demo)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Email itu sudah terdaftar.');

            return self::FAILURE;
        }

        $sandi = (string) $this->option('sandi');

        if ($sandi === '') {
            $sandi = (string) $this->secret('Kata sandi (minimal 8 karakter)');
            $ulang = (string) $this->secret('Ulangi kata sandi');

            if ($sandi !== $ulang) {
                $this->error('Kata sandi tidak sama.');

                return self::FAILURE;
            }
        }

        if (strlen($sandi) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');

            return self::FAILURE;
        }

        User::create([
            'name' => (string) $this->option('nama'),
            'email' => $email,
            'role' => 'admin',
            'password' => Hash::make($sandi),
        ]);

        $this->info('Admin dibuat. Login dengan email ' . $email . '.');

        return self::SUCCESS;
    }
}
