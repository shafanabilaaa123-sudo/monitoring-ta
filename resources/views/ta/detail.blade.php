@extends('layouts.app')

@section('judul', 'Detail Tugas Akhir')

@section('isi')
@php
    $u = auth()->user();
    $adalahMahasiswa = $u->role === 'mahasiswa';
    $adalahDosen = $u->role === 'dosen';
    $punyaDosen = $ta->dosen_id !== null;
    $bisaUbahTahapan = $punyaDosen && ($adalahMahasiswa || $adalahDosen) && $ta->status !== 'selesai';
    $daftarTahapan = $adalahDosen ? \App\Models\TugasAkhir::TAHAPAN : array_slice(\App\Models\TugasAkhir::TAHAPAN, 0, 5);
    $jadwalDisetujui = $ta->jadwal->where('status', 'disetujui');
@endphp

{{-- Info utama dan progres --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="mb-1">{{ $ta->judul }}</h4>
                <div class="text-muted">
                    Mahasiswa: <strong>{{ $ta->mahasiswa->name }}</strong> ({{ $ta->mahasiswa->nomor_induk ?: '-' }})
                    · Pembimbing: <strong>{{ $ta->dosen ? $ta->dosen->name : 'Belum ditetapkan' }}</strong>
                </div>
                @if($ta->deskripsi)
                    <p class="mt-2 mb-0">{{ $ta->deskripsi }}</p>
                @endif
            </div>
            <div class="text-end">
                <span class="badge text-bg-primary fs-6">{{ $ta->tahapan }}</span>
                @if($ta->tidakAktif())
                    <span class="badge text-bg-danger">Tidak aktif 14 hari</span>
                @endif
            </div>
        </div>

        <div class="progress my-3" style="height: 22px;">
            <div class="progress-bar" role="progressbar" style="width: {{ $ta->persen }}%">{{ $ta->persen }}%</div>
        </div>

        @unless($punyaDosen)
            <div class="alert alert-warning mb-0">Dosen pembimbing belum ditetapkan. Fitur bimbingan akan aktif setelah koordinator menetapkan pembimbing.</div>
        @endunless

        @if($bisaUbahTahapan)
            <form method="POST" action="{{ $adalahDosen ? route('dosen.tahapan', $ta) : route('mahasiswa.tahapan') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small mb-1">Perbarui tahapan</label>
                    <select name="tahapan" class="form-select form-select-sm">
                        @foreach($daftarTahapan as $tahap)
                            <option value="{{ $tahap }}" @selected($tahap === $ta->tahapan)>{{ $tahap }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Simpan tahapan</button>
                </div>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    {{-- Jadwal bimbingan --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Jadwal bimbingan</div>
            <div class="card-body">
                @if($adalahMahasiswa && $punyaDosen && $ta->status !== 'selesai')
                    <form method="POST" action="{{ route('mahasiswa.jadwal') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Waktu</label>
                            <input type="datetime-local" name="waktu" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Topik</label>
                            <input type="text" name="topik" class="form-control form-control-sm" maxlength="255" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-sm btn-primary w-100">Ajukan</button>
                        </div>
                    </form>
                    <hr>
                @endif

                @forelse($ta->jadwal as $j)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold">{{ $j->topik }}</div>
                                <div class="text-muted small">{{ $j->waktu->translatedFormat('l, d F Y H:i') }}</div>
                            </div>
                            <div><span class="badge text-bg-{{ $j->warnaBadge() }}">{{ $j->label() }}</span></div>
                        </div>

                        @if($j->status === 'ditolak' && $j->alasan_dosen)
                            <div class="small text-danger mt-1">Alasan: {{ $j->alasan_dosen }}</div>
                        @endif

                        @if($adalahDosen && $j->status === 'diajukan')
                            <div class="mt-2 d-grid gap-1">
                                <form method="POST" action="{{ route('dosen.jadwal', $j) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="aksi" value="setujui">
                                    <button class="btn btn-sm btn-success">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('dosen.jadwal', $j) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="aksi" value="ubah">
                                    <input type="datetime-local" name="waktu" class="form-control form-control-sm" required>
                                    <button class="btn btn-sm btn-outline-primary text-nowrap">Ubah waktu</button>
                                </form>
                                <form method="POST" action="{{ route('dosen.jadwal', $j) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="aksi" value="tolak">
                                    <input type="text" name="alasan" class="form-control form-control-sm" placeholder="Alasan penolakan" required>
                                    <button class="btn btn-sm btn-outline-danger">Tolak</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada jadwal bimbingan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Catatan bimbingan --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Catatan dan revisi dosen</div>
            <div class="card-body">
                @if($adalahDosen && $ta->dosen_id === $u->id)
                    <form method="POST" action="{{ route('dosen.catatan', $ta) }}" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small mb-1">Terkait jadwal (opsional, akan ditandai selesai)</label>
                            <select name="jadwal_bimbingan_id" class="form-select form-select-sm">
                                <option value="">Tanpa jadwal</option>
                                @foreach($jadwalDisetujui as $j)
                                    <option value="{{ $j->id }}">{{ $j->waktu->translatedFormat('d M Y H:i') }} · {{ $j->topik }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Catatan bimbingan</label>
                            <textarea name="isi" rows="3" class="form-control form-control-sm" required></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Instruksi revisi (opsional)</label>
                            <textarea name="revisi" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <button class="btn btn-sm btn-primary">Simpan catatan</button>
                    </form>
                    <hr>
                @endif

                @forelse($ta->catatan as $c)
                    <div class="border rounded p-2 mb-2">
                        <div class="text-muted small mb-1">{{ $c->created_at->translatedFormat('d F Y H:i') }}</div>
                        <div>{{ $c->isi }}</div>
                        @if($c->revisi)
                            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small"><strong>Revisi:</strong> {{ $c->revisi }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada catatan bimbingan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Dokumen --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Dokumen progres dan revisi</div>
            <div class="card-body">
                @if($adalahMahasiswa && $punyaDosen && $ta->status !== 'selesai')
                    <form method="POST" action="{{ route('mahasiswa.dokumen') }}" enctype="multipart/form-data" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Berkas (PDF, Word, PPT, ZIP, gambar; maks. 10 MB)</label>
                            <input type="file" name="berkas" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Keterangan (opsional)</label>
                            <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="255">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-sm btn-primary w-100">Unggah</button>
                        </div>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Berkas</th><th>Keterangan</th><th>Ukuran</th><th>Diunggah</th></tr></thead>
                        <tbody>
                        @forelse($ta->dokumen as $d)
                            <tr>
                                <td><a href="{{ route('dokumen.unduh', $d) }}">{{ $d->nama_asli }}</a></td>
                                <td>{{ $d->keterangan ?: '-' }}</td>
                                <td>{{ $d->ukuranTerbaca() }}</td>
                                <td>{{ $d->created_at->translatedFormat('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Belum ada dokumen.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
