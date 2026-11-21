<?php

namespace App\Services;

use App\Models\Notifikasi;

class Notifier
{
    /**
     * Simpan satu notifikasi untuk satu pengguna, lalu kirim juga sebagai Web Push
     * (muncul di layar HP/laptop walau aplikasi sedang ditutup).
     *
     * - Aplikasi terbuka: browser mengambil notifikasi tiap 3 detik (lihat layouts/app.blade.php).
     * - Aplikasi tertutup: Web Push dikirim setelah respons selesai, sehingga tidak memperlambat halaman.
     */
    public static function kirim(?int $userId, string $jenis, string $pesan, ?string $tautan = null): void
    {
        if (! $userId) {
            return;
        }

        $notif = Notifikasi::create([
            'user_id' => $userId,
            'jenis' => $jenis,
            'pesan' => mb_substr($pesan, 0, 500),
            'tautan' => $tautan,
        ]);

        if (WebPushService::tersedia()) {
            $payload = [
                'title' => 'Monitoring TA',
                'body' => $notif->pesan,
                'url' => $tautan ?: '/dashboard',
                'tag' => 'notif-' . $notif->id,
            ];

            app()->terminating(function () use ($userId, $payload) {
                WebPushService::kirimKeUser($userId, $payload);
            });
        }
    }
}
