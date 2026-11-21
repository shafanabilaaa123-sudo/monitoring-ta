@extends('layouts.app')

@section('judul', 'Notifikasi')

@section('isi')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 judul-halaman">
    <h4 class="mb-0">Semua Notifikasi</h4>

    @if(config('webpush.public_key'))
        <form method="POST" action="{{ route('push.uji') }}">
            @csrf
            <button class="btn btn-sm btn-outline-primary">Kirim notifikasi uji ke perangkatku</button>
        </form>
    @endif
</div>

<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($daftar as $n)
            <a href="{{ route('notifikasi.buka', $n) }}" class="list-group-item list-group-item-action {{ in_array($n->id, $baruIds) ? 'notif-item baru' : '' }}">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        {{ $n->pesan }}
                        @if(in_array($n->id, $baruIds)) <span class="badge text-bg-primary ms-1">Baru</span> @endif
                    </div>
                    <div class="text-muted small text-nowrap">{{ $n->created_at->diffForHumans() }}</div>
                </div>
            </a>
        @empty
            <div class="list-group-item text-muted">Belum ada notifikasi.</div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $daftar->links() }}</div>
@endsection
