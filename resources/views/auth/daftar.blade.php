@extends('layouts.app')

@section('judul', 'Daftar')

@section('isi')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="tamu-kepala">
            @include('partials.logo', ['ukuran' => 84])
            <h1>Buat Akun</h1>
            <p>Daftar untuk memantau progres dan bimbingan tugas akhir</p>
        </div>

        <div class="card tamu-kartu">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('daftar.proses') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Daftar sebagai</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="mahasiswa" @selected(old('role', 'mahasiswa') === 'mahasiswa')>Mahasiswa</option>
                            @if($dosenBisaDaftar)
                                <option value="dosen" @selected(old('role') === 'dosen')>Dosen pembimbing</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" maxlength="100" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="label-induk">NIM</label>
                        <input type="text" name="nomor_induk" value="{{ old('nomor_induk') }}" class="form-control" maxlength="30">
                    </div>

                    <div class="mb-3" id="kotak-kode" style="display:none">
                        <label class="form-label">Kode pendaftaran dosen</label>
                        <input type="text" name="kode_dosen" value="{{ old('kode_dosen') }}" class="form-control" autocomplete="off">
                        <div class="form-text">Minta kode ini ke koordinator program studi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kata sandi (min. 6 karakter)</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ulangi kata sandi</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                    </div>

                    <button class="btn btn-kuning w-100">Daftar</button>
                </form>
            </div>
        </div>

        <p class="text-center tamu-bawah mt-3 mb-4">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </div>
</div>

<script>
    // Tampilkan kolom kode hanya untuk dosen, dan ubah label NIM/NIP
    (function () {
        var peran = document.getElementById('role');
        var kotak = document.getElementById('kotak-kode');
        var label = document.getElementById('label-induk');

        function atur() {
            var dosen = peran.value === 'dosen';
            kotak.style.display = dosen ? '' : 'none';
            label.textContent = dosen ? 'NIP (opsional)' : 'NIM';
        }

        peran.addEventListener('change', atur);
        atur();
    })();
</script>
@endsection
