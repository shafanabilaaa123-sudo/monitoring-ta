@extends('layouts.app')

@section('judul', 'Ajukan Judul')

@section('isi')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-1">Ajukan Judul Tugas Akhir</h4>
                <p class="text-muted">Setelah diajukan, koordinator akan menetapkan dosen pembimbing untukmu.</p>

                <form method="POST" action="{{ route('mahasiswa.ta') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" name="judul" value="{{ old('judul') }}" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi singkat (opsional)</label>
                        <textarea name="deskripsi" rows="4" class="form-control" maxlength="2000">{{ old('deskripsi') }}</textarea>
                    </div>
                    <button class="btn btn-primary">Ajukan judul</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
