<?php

namespace App\Http\Controllers;

use App\Models\CatatanBimbingan;
use App\Models\JadwalBimbingan;
use App\Models\TugasAkhir;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DosenController extends Controller
{
    /** Dosen menyetujui, menolak, atau mengubah waktu jadwal bimbingan. */
    public function keputusanJadwal(Request $request, JadwalBimbingan $jadwal)
    {
        $ta = $jadwal->tugasAkhir;
        abort_unless($ta->dosen_id === $request->user()->id, 403);
        abort_unless($jadwal->status === 'diajukan', 403, 'Jadwal ini sudah diproses.');

        $data = $request->validate([
            'aksi' => ['required', Rule::in(['setujui', 'tolak', 'ubah'])],
            'waktu' => ['required_if:aksi,ubah', 'nullable', 'date', 'after:now'],
            'alasan' => ['required_if:aksi,tolak', 'nullable', 'string', 'max:500'],
        ]);

        $tautan = route('ta.detail', $ta, false);
        $dosen = $request->user()->name;

        if ($data['aksi'] === 'setujui') {
            $jadwal->update(['status' => 'disetujui']);
            $pesan = $dosen . ' menyetujui bimbingan pada ' . $jadwal->waktu->translatedFormat('d F Y H:i') . '.';
        } elseif ($data['aksi'] === 'ubah') {
            $jadwal->update([
                'waktu' => $data['waktu'],
                'status' => 'disetujui',
                'diingatkan' => false,
            ]);
            $jadwal->refresh();
            $pesan = $dosen . ' mengubah jadwal bimbingan menjadi ' . $jadwal->waktu->translatedFormat('d F Y H:i') . '.';
        } else {
            $jadwal->update(['status' => 'ditolak', 'alasan_dosen' => $data['alasan']]);
            $pesan = $dosen . ' menolak jadwal bimbingan. Alasan: ' . $data['alasan'];
        }

        $ta->catatAktivitas();
        Notifier::kirim($ta->mahasiswa_id, 'jadwal', $pesan, $tautan);

        return back()->with('sukses', 'Keputusan jadwal disimpan dan mahasiswa sudah diberi notifikasi.');
    }

    /** Dosen mencatat hasil bimbingan dan instruksi revisi. */
    public function simpanCatatan(Request $request, TugasAkhir $ta)
    {
        abort_unless($ta->dosen_id === $request->user()->id, 403);

        $data = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
            'revisi' => ['nullable', 'string', 'max:5000'],
            'jadwal_bimbingan_id' => ['nullable', 'integer'],
        ]);

        $jadwalId = null;
        if (! empty($data['jadwal_bimbingan_id'])) {
            $jadwal = $ta->jadwal()->where('id', $data['jadwal_bimbingan_id'])->first();
            abort_if(! $jadwal, 422, 'Jadwal tidak valid.');
            $jadwal->update(['status' => 'selesai']);
            $jadwalId = $jadwal->id;
        }

        CatatanBimbingan::create([
            'tugas_akhir_id' => $ta->id,
            'jadwal_bimbingan_id' => $jadwalId,
            'isi' => $data['isi'],
            'revisi' => $data['revisi'] ?? null,
        ]);
        $ta->catatAktivitas();

        $pesan = $request->user()->name . ' menambahkan catatan bimbingan';
        $pesan .= ! empty($data['revisi']) ? ' dengan instruksi revisi.' : '.';
        Notifier::kirim($ta->mahasiswa_id, 'catatan', $pesan, route('ta.detail', $ta, false));

        return back()->with('sukses', 'Catatan bimbingan disimpan dan mahasiswa sudah diberi notifikasi.');
    }

    /** Dosen mengubah tahapan TA (termasuk menandai "Selesai"). */
    public function ubahTahapan(Request $request, TugasAkhir $ta)
    {
        abort_unless($ta->dosen_id === $request->user()->id, 403);

        $data = $request->validate([
            'tahapan' => ['required', Rule::in(TugasAkhir::TAHAPAN)],
        ]);

        $ta->update([
            'tahapan' => $data['tahapan'],
            'status' => $data['tahapan'] === 'Selesai' ? 'selesai' : 'berjalan',
            'aktivitas_terakhir' => now(),
        ]);

        Notifier::kirim(
            $ta->mahasiswa_id,
            'tahapan',
            'Tahapan TA kamu diperbarui dosen menjadi "' . $data['tahapan'] . '".',
            route('ta.detail', $ta, false)
        );

        return back()->with('sukses', 'Tahapan diperbarui dan mahasiswa sudah diberi notifikasi.');
    }
}
