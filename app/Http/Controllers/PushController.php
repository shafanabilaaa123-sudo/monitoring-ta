<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\Notifier;
use Illuminate\Http\Request;

class PushController extends Controller
{
    /** Simpan (atau perbarui) langganan push milik perangkat ini untuk pengguna yang login. */
    public function daftar(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /** Hapus langganan perangkat ini (saat dimatikan atau saat keluar akun). */
    public function batal(Request $request)
    {
        $endpoint = (string) $request->input('endpoint');

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $endpoint))
            ->delete();

        return response()->json(['ok' => true]);
    }

    /** Kirim notifikasi uji ke semua perangkat milik pengguna yang login. */
    public function uji(Request $request)
    {
        Notifier::kirim(
            $request->user()->id,
            'uji',
            'Ini notifikasi uji. Kalau muncul di layar perangkatmu, notifikasi sudah aktif.',
            route('dashboard', [], false)
        );

        return back()->with('sukses', 'Notifikasi uji dikirim. Tutup aplikasi lalu tunggu beberapa detik untuk melihat push di layar.');
    }
}
