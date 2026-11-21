@extends('layouts.app')

@section('judul', 'Kelola Pengguna')

@section('isi')
<h4 class="mb-3 judul-halaman">Kelola Pengguna</h4>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Tambah pengguna</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.pengguna.simpan') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Peran</label>
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="mahasiswa" @selected(old('role') === 'mahasiswa')>Mahasiswa</option>
                            <option value="dosen" @selected(old('role') === 'dosen')>Dosen</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">NIM / NIP</label>
                        <input type="text" name="nomor_induk" value="{{ old('nomor_induk') }}" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small mb-1">Kata sandi (min. 6 karakter)</label>
                        <input type="text" name="password" class="form-control form-control-sm" required>
                    </div>
                    <button class="btn btn-primary btn-sm">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>NIM / NIP</th><th></th></tr></thead>
                    <tbody>
                    @foreach($pengguna as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td>{{ $p->email }}</td>
                            <td><span class="badge text-bg-secondary">{{ $p->labelPeran() }}</span></td>
                            <td>{{ $p->nomor_induk ?: '-' }}</td>
                            <td class="text-end">
                                @if($p->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.pengguna.hapus', $p) }}" onsubmit="return confirm('Hapus pengguna ini beserta datanya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
