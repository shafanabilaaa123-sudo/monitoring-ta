<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /** Halaman semua notifikasi. Membukanya menandai semua sebagai sudah dibaca. */
    public function index(Request $request)
    {
        $user = $request->user();

        $daftar = $user->notifikasi()->latest('id')->paginate(20);
        $baruIds = $daftar->getCollection()->where('sudah_dibaca', false)->pluck('id')->all();

        $user->notifikasi()->where('sudah_dibaca', false)->update(['sudah_dibaca' => true]);

        return view('notifikasi.index', compact('daftar', 'baruIds'));
    }

    /** Dipanggil browser tiap beberapa detik: kembalikan notifikasi yang lebih baru dari ID terakhir. */
    public function baru(Request $request)
    {
        $setelah = (int) $request->query('setelah', 0);
        $userId = $request->user()->id;

        $baru = Notifikasi::where('user_id', $userId)
            ->where('id', '>', $setelah)
            ->orderBy('id')
            ->get();

        return response()->json([
            'belum_dibaca' => Notifikasi::where('user_id', $userId)->where('sudah_dibaca', false)->count(),
            'baru' => $baru->map(fn ($n) => [
                'id' => $n->id,
                'jenis' => $n->jenis,
                'pesan' => $n->pesan,
                'waktu' => $n->created_at->diffForHumans(),
            ])->values(),
        ]);
    }

    /** Tandai satu notifikasi dibaca lalu arahkan ke tautannya. */
    public function buka(Request $request, Notifikasi $notifikasi)
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);

        $notifikasi->update(['sudah_dibaca' => true]);

        return redirect($notifikasi->tautan ?: route('dashboard'));
    }
}
