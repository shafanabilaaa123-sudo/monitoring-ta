<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class BuatKunciPush extends Command
{
    protected $signature = 'push:keys {--force : Timpa kunci yang sudah ada}';

    protected $description = 'Buat kunci VAPID untuk Web Push dan simpan ke file .env';

    public function handle(): int
    {
        if (! class_exists(VAPID::class)) {
            $this->error('Paket belum terpasang. Jalankan dulu: composer require minishlink/web-push');

            return self::FAILURE;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->error('File .env tidak ditemukan.');

            return self::FAILURE;
        }

        $env = file_get_contents($envPath);

        if (preg_match('/^VAPID_PUBLIC_KEY=.+$/m', $env) && ! $this->option('force')) {
            $this->info('Kunci VAPID sudah ada di .env. Pakai --force untuk membuat ulang (semua langganan lama jadi tidak berlaku).');

            return self::SUCCESS;
        }

        $kunci = VAPID::createVAPIDKeys();

        // Buang baris lama, lalu tambahkan yang baru
        $env = preg_replace('/^VAPID_(PUBLIC_KEY|PRIVATE_KEY|SUBJECT)=.*\R?/m', '', $env);
        $env = rtrim($env) . "\n\n"
            . 'VAPID_PUBLIC_KEY=' . $kunci['publicKey'] . "\n"
            . 'VAPID_PRIVATE_KEY=' . $kunci['privateKey'] . "\n"
            . "VAPID_SUBJECT=mailto:admin@kampus.test\n";

        file_put_contents($envPath, $env);

        $this->info('Kunci VAPID dibuat dan disimpan di .env.');
        $this->line('Jalankan: php artisan config:clear');

        return self::SUCCESS;
    }
}
