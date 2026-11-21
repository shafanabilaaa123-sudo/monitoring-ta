@extends('layouts.app')

@section('judul', 'Masuk')

@section('isi')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="tamu-kepala">
            @include('partials.logo', ['ukuran' => 108])
            <div class="kampus">{{ config('kampus.nama') }}</div>
            <h1>Monitoring Tugas Akhir</h1>
            <p>Pantau progres dan bimbingan TA secara real time</p>
        </div>

        <div class="card tamu-kartu">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Masuk ke akun</h5>
                <form method="POST" action="{{ route('login.proses') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="nama@email.com" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata sandi</label>
                        <input type="password" name="password" class="form-control" placeholder="Kata sandi" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="ingat" id="ingat" value="1">
                        <label class="form-check-label small" for="ingat">Ingat saya</label>
                    </div>
                    <button class="btn btn-kuning w-100">Masuk</button>
                </form>
            </div>
        </div>

        @if(config('pendaftaran.aktif'))
            <p class="text-center tamu-bawah mt-3 mb-0">Belum punya akun? <a href="{{ route('daftar') }}">Daftar</a></p>
        @endif

        <div class="pb-4"></div>
    </div>
</div>
@endsection
