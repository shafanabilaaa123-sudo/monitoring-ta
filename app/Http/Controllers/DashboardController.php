<?php

namespace App\Http\Controllers;

use App\Models\JadwalBimbingan;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $semua = TugasAkhir::with(['mahasiswa', 'dosen'])->latest()->get();
            $dosen = User::where('role', 'dosen')->orderBy('name')->get();
            $stat = [
                'total' => $semua->count(),
                'menunggu' => $semua->whereNull('dosen_id')->count(),
                'berjalan' => $semua->where('status', 'berjalan')->count(),
                'tidakAktif' => $semua->filter(fn ($t) => $t->tidakAktif())->count(),
                'selesai' => $semua->where('status', 'selesai')->count(),
                'belumMengajukan' => User::where('role', 'mahasiswa')->doesntHave('tugasAkhir')->count(),
            ];

            return view('dashboard.admin', compact('semua', 'dosen', 'stat'));
        }

        if ($user->role === 'dosen') {
            $bimbingan = $user->bimbingan()->with('mahasiswa')->get();

            $jadwalDosen = JadwalBimbingan::with('tugasAkhir.mahasiswa')
                ->whereHas('tugasAkhir', fn ($q) => $q->where('dosen_id', $user->id));

            $menunggu = (clone $jadwalDosen)->where('status', 'diajukan')->orderBy('waktu')->get();
            $mendatang = (clone $jadwalDosen)->where('status', 'disetujui')
                ->where('waktu', '>=', now())->orderBy('waktu')->get();

            return view('dashboard.dosen', compact('bimbingan', 'menunggu', 'mendatang'));
        }

        // Mahasiswa
        $ta = $user->tugasAkhir;
        if ($ta) {
            return redirect()->route('ta.detail', $ta);
        }

        return view('dashboard.mahasiswa_baru');
    }

    public function detail(Request $request, TugasAkhir $ta)
    {
        abort_unless($ta->dapatDiaksesOleh($request->user()), 403);

        $ta->load([
            'mahasiswa',
            'dosen',
            'jadwal' => fn ($q) => $q->orderByDesc('waktu'),
            'catatan' => fn ($q) => $q->latest('id'),
            'dokumen' => fn ($q) => $q->latest('id'),
        ]);

        return view('ta.detail', compact('ta'));
    }
}
