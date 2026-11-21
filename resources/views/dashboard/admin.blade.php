@extends('layouts.app')

@section('judul', 'Dashboard Admin')

@section('isi')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 judul-halaman">
    <h4 class="mb-0">Ringkasan Tugas Akhir</h4>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.cadangan') }}">Unduh salinan data (JSON)</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Total TA</div>
            <div class="fs-3 fw-bold">{{ $stat['total'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Belum ada pembimbing</div>
            <div class="fs-3 fw-bold text-warning">{{ $stat['menunggu'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Berjalan</div>
            <div class="fs-3 fw-bold text-primary">{{ $stat['berjalan'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Tidak aktif 14 hari</div>
            <div class="fs-3 fw-bold text-danger">{{ $stat['tidakAktif'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Selesai</div>
            <div class="fs-3 fw-bold text-success">{{ $stat['selesai'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Belum mengajukan judul</div>
            <div class="fs-3 fw-bold">{{ $stat['belumMengajukan'] }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold">Semua tugas akhir</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Mahasiswa</th><th>Judul</th><th style="min-width: 260px;">Dosen pembimbing</th><th>Tahapan</th><th>Progres</th><th>Aktivitas terakhir</th></tr>
            </thead>
            <tbody>
            @forelse($semua as $t)
                <tr>
                    <td>{{ $t->mahasiswa->name }}<br><span class="text-muted small">{{ $t->mahasiswa->nomor_induk }}</span></td>
                    <td><a href="{{ route('ta.detail', $t) }}">{{ $t->judul }}</a></td>
                    <td>
                        <form method="POST" action="{{ route('admin.pembimbing', $t) }}" class="d-flex gap-1">
                            @csrf
                            <select name="dosen_id" class="form-select form-select-sm" required>
                                <option value="">Pilih dosen</option>
                                @foreach($dosen as $d)
                                    <option value="{{ $d->id }}" @selected($t->dosen_id === $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-primary">{{ $t->dosen_id ? 'Ganti' : 'Tetapkan' }}</button>
                        </form>
                    </td>
                    <td><span class="badge text-bg-primary">{{ $t->tahapan }}</span></td>
                    <td>
                        <div class="progress progress-kecil"><div class="progress-bar" style="width: {{ $t->persen }}%"></div></div>
                        <span class="small text-muted">{{ $t->persen }}%</span>
                    </td>
                    <td>
                        {{ $t->aktivitas_terakhir ? $t->aktivitas_terakhir->diffForHumans() : '-' }}
                        @if($t->tidakAktif()) <span class="badge text-bg-danger">Tidak aktif</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Belum ada tugas akhir yang diajukan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
