<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\JadwalBimbingan;
use App\Models\TugasAkhir;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MahasiswaController extends Controller
{
    /** Mahasiswa mengajukan judul TA. */
    public function ajukanJudul(Request $request)
    {
        $user = $request->user();
        abort_if($user->tugasAkhir()->exists(), 403, 'Kamu sudah mengajukan judul.');

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
        ]);

        $ta = TugasAkhir::create($data + [
            'mahasiswa_id' => $user->id,
            'tahapan' => 'Pengajuan Judul',
            'status' => 'menunggu_pembimbing',
            'aktivitas_terakhir' => now(),
        ]);

        $tautan = route('ta.detail', $ta, false);
        foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
            Notifier::kirim($adminId, 'judul', $user->name . ' mengajukan judul TA. Tetapkan dosen pembimbing.', $tautan);
        }

        return redirect()->route('ta.detail', $ta)
            ->with('sukses', 'Judul berhasil diajukan. Menunggu penetapan dosen pembimbing.');
    }

    /** Mahasiswa mengajukan jadwal bimbingan. */
    public function ajukanJadwal(Request $request)
    {
        $ta = $this->taSaya($request);

        $data = $request->validate([
            'waktu' => ['required', 'date', 'after:now'],
            'topik' => ['required', 'string', 'max:255'],
        ]);

        $jadwal = JadwalBimbingan::create($data + [
            'tugas_akhir_id' => $ta->id,
            'status' => 'diajukan',
        ]);
        $ta->catatAktivitas();

        Notifier::kirim(
            $ta->dosen_id,
            'jadwal',
            $request->user()->name . ' mengajukan bimbingan pada ' . $jadwal->waktu->translatedFormat('d F Y H:i') . ' (' . $jadwal->topik . ').',
            route('ta.detail', $ta, false)
        );

        return back()->with('sukses', 'Jadwal bimbingan diajukan. Menunggu persetujuan dosen.');
    }

    /** Mahasiswa mengunggah dokumen progres/revisi. */
    public function unggahDokumen(Request $request)
    {
        $ta = $this->taSaya($request);

        $request->validate([
            'berkas' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,zip,png,jpg,jpeg'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $berkas = $request->file('berkas');
        $ukuran = $berkas->getSize();
        $nama = $berkas->getClientOriginalName();
        $path = $berkas->store('dokumen-ta', 'public');

        Dokumen::create([
            'tugas_akhir_id' => $ta->id,
            'nama_asli' => $nama,
            'path' => $path,
            'ukuran' => $ukuran,
            'keterangan' => $request->input('keterangan'),
        ]);
        $ta->catatAktivitas();

        Notifier::kirim(
            $ta->dosen_id,
            'dokumen',
            $request->user()->name . ' mengunggah dokumen: ' . $nama . '.',
            route('ta.detail', $ta, false)
        );

        return back()->with('sukses', 'Dokumen berhasil diunggah.');
    }

    /** Mahasiswa memperbarui tahapan (maksimal "Siap Sidang"; "Selesai" hanya oleh dosen). */
    public function ubahTahapan(Request $request)
    {
        $ta = $this->taSaya($request);

        $data = $request->validate([
            'tahapan' => ['required', Rule::in(array_slice(TugasAkhir::TAHAPAN, 0, 5))],
        ]);

        $ta->update(['tahapan' => $data['tahapan'], 'aktivitas_terakhir' => now()]);

        Notifier::kirim(
            $ta->dosen_id,
            'tahapan',
            $request->user()->name . ' memperbarui tahapan TA menjadi "' . $data['tahapan'] . '".',
            route('ta.detail', $ta, false)
        );

        return back()->with('sukses', 'Tahapan diperbarui.');
    }

    /** TA milik mahasiswa yang login, harus sudah punya pembimbing. */
    private function taSaya(Request $request): TugasAkhir
    {
        $ta = $request->user()->tugasAkhir;
        abort_if(! $ta, 403, 'Ajukan judul TA terlebih dahulu.');
        abort_if(! $ta->dosen_id, 403, 'Dosen pembimbing belum ditetapkan.');
        abort_if($ta->status === 'selesai', 403, 'TA sudah selesai.');

        return $ta;
    }
}
