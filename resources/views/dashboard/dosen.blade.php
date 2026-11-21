@extends('layouts.app')

@section('judul', 'Dashboard Dosen')

@section('isi')
<h4 class="mb-3 judul-halaman">Halo, {{ auth()->user()->name }}</h4>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Mahasiswa bimbingan</div>
            <div class="fs-3 fw-bold">{{ $bimbingan->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Jadwal menunggu persetujuan</div>
            <div class="fs-3 fw-bold text-warning">{{ $menunggu->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Mahasiswa tanpa aktivitas 14 hari</div>
            <div class="fs-3 fw-bold text-danger">{{ $bimbingan->filter(fn ($t) => $t->tidakAktif())->count() }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Menunggu persetujuan</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Mahasiswa</th><th>Waktu diajukan</th><th>Topik</th><th></th></tr></thead>
            <tbody>
            @forelse($menunggu as $j)
                <tr>
                    <td>{{ $j->tugasAkhir->mahasiswa->name }}</td>
                    <td>{{ $j->waktu->translatedFormat('l, d F Y H:i') }}</td>
                    <td>{{ $j->topik }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('ta.detail', $j->tugasAkhir) }}">Tinjau</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Tidak ada jadwal yang menunggu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Bimbingan mendatang</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Mahasiswa</th><th>Waktu</th><th>Topik</th></tr></thead>
            <tbody>
            @forelse($mendatang as $j)
                <tr>
                    <td><a href="{{ route('ta.detail', $j->tugasAkhir) }}">{{ $j->tugasAkhir->mahasiswa->name }}</a></td>
                    <td>{{ $j->waktu->translatedFormat('l, d F Y H:i') }}</td>
                    <td>{{ $j->topik }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted">Belum ada bimbingan terjadwal.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold">Mahasiswa bimbingan</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Tahapan</th><th>Progres</th><th>Aktivitas terakhir</th></tr></thead>
            <tbody>
            @forelse($bimbingan as $t)
                <tr>
                    <td>{{ $t->mahasiswa->name }}<br><span class="text-muted small">{{ $t->mahasiswa->nomor_induk }}</span></td>
                    <td><a href="{{ route('ta.detail', $t) }}">{{ $t->judul }}</a></td>
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
                <tr><td colspan="5" class="text-muted">Belum ada mahasiswa bimbingan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
