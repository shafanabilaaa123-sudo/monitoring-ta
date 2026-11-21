<?php

namespace App\Http\Controllers;

use App\Models\TugasAkhir;
use App\Models\User;
use App\Services\CadanganData;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function pengguna()
    {
        $pengguna = User::orderBy('role')->orderBy('name')->get();

        return view('admin.pengguna', compact('pengguna'));
    }

    public function cadangan(CadanganData $cadangan)
    {
        $nama = 'cadangan-monitoring-ta-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($cadangan) {
            echo $cadangan->keJson();
        }, $nama, ['Content-Type' => 'application/json']);
    }

    public function simpanPengguna(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['mahasiswa', 'dosen', 'admin'])],
            'nomor_induk' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return back()->with('sukses', 'Pengguna berhasil ditambahkan.');
    }

    public function hapusPengguna(Request $request, User $pengguna)
    {
        abort_if($pengguna->id === $request->user()->id, 403, 'Kamu tidak bisa menghapus akunmu sendiri.');

        $pengguna->delete();

        return back()->with('sukses', 'Pengguna dihapus.');
    }

    /** Admin menetapkan / mengganti dosen pembimbing. */
    public function tetapkanPembimbing(Request $request, TugasAkhir $ta)
    {
        $data = $request->validate([
            'dosen_id' => ['required', Rule::exists('users', 'id')->where('role', 'dosen')],
        ]);

        $dosenBaruId = (int) $data['dosen_id'];
        $dosenLamaId = $ta->dosen_id;

        $ta->update([
            'dosen_id' => $dosenBaruId,
            'status' => $ta->status === 'selesai' ? 'selesai' : 'berjalan',
            'tahapan' => $ta->tahapan === 'Pengajuan Judul' ? 'Proposal' : $ta->tahapan,
            'aktivitas_terakhir' => now(),
        ]);

        $ta->load(['mahasiswa', 'dosen']);
        $tautan = route('ta.detail', $ta, false);

        Notifier::kirim($ta->mahasiswa_id, 'pembimbing', 'Dosen pembimbing kamu ditetapkan: ' . $ta->dosen->name . '.', $tautan);
        Notifier::kirim($dosenBaruId, 'pembimbing', 'Kamu ditetapkan sebagai pembimbing ' . $ta->mahasiswa->name . '.', $tautan);

        if ($dosenLamaId && $dosenLamaId !== $dosenBaruId) {
            Notifier::kirim($dosenLamaId, 'pembimbing', 'Bimbingan ' . $ta->mahasiswa->name . ' dialihkan ke dosen lain.', null);
        }

        return back()->with('sukses', 'Dosen pembimbing ditetapkan dan pihak terkait sudah diberi notifikasi.');
    }
}
