<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    /** Web Push aktif hanya jika paket minishlink/web-push terpasang dan kunci VAPID sudah dibuat. */
    public static function tersedia(): bool
    {
        return class_exists(WebPush::class)
            && config('webpush.public_key')
            && config('webpush.private_key');
    }

    /** Kirim push ke semua perangkat milik satu pengguna. Tidak pernah melempar error ke pemanggil. */
    public static function kirimKeUser(int $userId, array $payload): void
    {
        if (! self::tersedia()) {
            return;
        }

        try {
            $langganan = PushSubscription::where('user_id', $userId)->get();
            if ($langganan->isEmpty()) {
                return;
            }

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('webpush.subject'),
                    'publicKey' => config('webpush.public_key'),
                    'privateKey' => config('webpush.private_key'),
                ],
            ], [], 10);

            $isi = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($langganan as $l) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $l->endpoint,
                        'publicKey' => $l->public_key,
                        'authToken' => $l->auth_token,
                        'contentEncoding' => $l->content_encoding,
                    ]),
                    $isi
                );
            }

            foreach ($webPush->flush() as $laporan) {
                if ($laporan->isSubscriptionExpired()) {
                    // Perangkat sudah berhenti berlangganan: bersihkan dari database
                    PushSubscription::where('endpoint_hash', hash('sha256', $laporan->getEndpoint()))->delete();
                } elseif (! $laporan->isSuccess()) {
                    Log::warning('Web Push gagal: ' . $laporan->getReason());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Web Push error: ' . $e->getMessage());
        }
    }
}
