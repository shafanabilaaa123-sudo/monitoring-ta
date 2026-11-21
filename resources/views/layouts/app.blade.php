<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Monitoring TA') · Monitoring TA</title>

    {{-- PWA: agar bisa dipasang seperti aplikasi --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#14507f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Monitoring TA">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/tema.css') }}?v={{ filemtime(public_path('css/tema.css')) }}" rel="stylesheet">
</head>
<body class="@auth punya-nav @else halaman-tamu @endauth">
@auth
    @php
        $penggunaLogin = auth()->user();
        $belumDibaca = $penggunaLogin->notifikasi()->where('sudah_dibaca', false)->count();
        $notifTerbaru = $penggunaLogin->notifikasi()->latest('id')->limit(5)->get();
        $idTerakhir = (int) $penggunaLogin->notifikasi()->max('id');
        $kunciPush = (string) config('webpush.public_key');
    @endphp
    @php
        $inisial = mb_strtoupper(mb_substr(trim($penggunaLogin->name), 0, 1));
        $subJudul = $penggunaLogin->nomor_induk ?: $penggunaLogin->labelPeran();
        $rutAktif = request()->routeIs('notifikasi.*') ? 'notifikasi' : (request()->routeIs('admin.pengguna*') ? 'pengguna' : 'beranda');
    @endphp
    <header class="hero">
        <div class="container">
            <div class="hero-merek">Monitoring Tugas Akhir</div>
            <div class="hero-baris">
                <div class="hero-pengguna">
                    <div class="avatar">{{ $inisial }}</div>
                    <div style="min-width:0">
                        <div class="hero-nama">{{ $penggunaLogin->name }}</div>
                        <div class="hero-sub">{{ $subJudul }}@if($penggunaLogin->nomor_induk) · {{ $penggunaLogin->labelPeran() }}@endif</div>
                    </div>
                </div>

                <div class="hero-kanan">
                    @if($penggunaLogin->role === 'admin')
                        <a class="hero-tautan d-none d-md-inline me-2" href="{{ route('admin.pengguna') }}">Pengguna</a>
                    @endif

                    <div class="dropdown">
                        <button class="tombol-hero" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Notifikasi">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="align-text-bottom"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <span class="teks ms-1">Notifikasi</span>
                            <span id="notif-badge" class="badge rounded-pill bg-danger js-notif-badge" style="{{ $belumDibaca > 0 ? '' : 'display:none' }}">{{ $belumDibaca }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 border-0 shadow" style="width: min(340px, 92vw); border-radius: 1rem; overflow: hidden;">
                            <div id="notif-daftar" class="list-group list-group-flush">
                                @forelse($notifTerbaru as $n)
                                    <a href="{{ route('notifikasi.buka', $n) }}" class="list-group-item list-group-item-action notif-item {{ $n->sudah_dibaca ? '' : 'baru' }}">
                                        <div class="small">{{ $n->pesan }}</div>
                                        <div class="text-muted" style="font-size: 12px;">{{ $n->created_at->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div id="notif-kosong" class="list-group-item text-muted small">Belum ada notifikasi.</div>
                                @endforelse
                            </div>
                            <a class="dropdown-item text-center small py-2 border-top" href="{{ route('notifikasi.index') }}">Lihat semua notifikasi</a>
                            @if($kunciPush !== '')
                                <button type="button" id="btn-push" class="dropdown-item text-center small py-2 border-top text-primary" style="display:none"></button>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" id="form-keluar" class="d-none d-md-block">
                        @csrf
                        <button class="tombol-hero">Keluar</button>
                    </form>

                    @include('partials.logo', ['ukuran' => 46])
                </div>
            </div>
        </div>
    </header>

    {{-- Navigasi bawah: hanya tampil di HP --}}
    <nav class="nav-bawah" aria-label="Menu utama">
        <a href="{{ route('dashboard') }}" class="item {{ $rutAktif === 'beranda' ? 'aktif' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
            Beranda
        </a>
        @if($penggunaLogin->role === 'admin')
            <a href="{{ route('admin.pengguna') }}" class="item {{ $rutAktif === 'pengguna' ? 'aktif' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.2A6.5 6.5 0 0 1 21.5 20"/></svg>
                Pengguna
            </a>
        @endif
        <a href="{{ route('notifikasi.index') }}" class="item {{ $rutAktif === 'notifikasi' ? 'aktif' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            Notifikasi
            <span class="badge rounded-pill bg-danger js-notif-badge" style="{{ $belumDibaca > 0 ? '' : 'display:none' }}">{{ $belumDibaca }}</span>
        </a>
        <button type="submit" form="form-keluar" class="item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
            Keluar
        </button>
    </nav>
@endauth

<main class="container pb-5 @auth konten @endauth">
    @if(session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $pesanError)
                    <li>{{ $pesanError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('isi')
</main>

<div id="toast-wadah" class="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Daftarkan service worker (syarat aplikasi bisa diinstal dan menerima push)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
</script>
@auth
<script>
    // Notifikasi real time saat aplikasi terbuka: browser menanyakan server tiap 3 detik,
    // lalu menampilkan notifikasi baru tanpa refresh.
    (function () {
        var terakhir = {{ $idTerakhir }};
        var urlBaru = "{{ route('notifikasi.baru') }}";
        var urlBuka = "{{ url('/notifikasi') }}";
        var badges = document.querySelectorAll('.js-notif-badge');
        var daftar = document.getElementById('notif-daftar');

        function tampilToast(n) {
            var wadah = document.getElementById('toast-wadah');
            var el = document.createElement('div');
            el.className = 'toast align-items-center text-bg-dark border-0';
            el.setAttribute('role', 'alert');

            var baris = document.createElement('div');
            baris.className = 'd-flex';
            var badan = document.createElement('div');
            badan.className = 'toast-body';
            var tautan = document.createElement('a');
            tautan.href = urlBuka + '/' + n.id + '/buka';
            tautan.className = 'text-white text-decoration-none';
            tautan.textContent = n.pesan;
            badan.appendChild(tautan);

            var tutup = document.createElement('button');
            tutup.type = 'button';
            tutup.className = 'btn-close btn-close-white me-2 m-auto';
            tutup.setAttribute('data-bs-dismiss', 'toast');

            baris.appendChild(badan);
            baris.appendChild(tutup);
            el.appendChild(baris);
            wadah.appendChild(el);

            var toast = new bootstrap.Toast(el, { delay: 7000 });
            el.addEventListener('hidden.bs.toast', function () { el.remove(); });
            toast.show();
        }

        function tambahKeDaftar(n) {
            var kosong = document.getElementById('notif-kosong');
            if (kosong) { kosong.remove(); }

            var a = document.createElement('a');
            a.href = urlBuka + '/' + n.id + '/buka';
            a.className = 'list-group-item list-group-item-action notif-item baru';
            var isi = document.createElement('div');
            isi.className = 'small';
            isi.textContent = n.pesan;
            var waktu = document.createElement('div');
            waktu.className = 'text-muted';
            waktu.style.fontSize = '12px';
            waktu.textContent = n.waktu;
            a.appendChild(isi);
            a.appendChild(waktu);
            daftar.insertBefore(a, daftar.firstChild);
        }

        function cek() {
            if (document.hidden) { return; }
            fetch(urlBaru + '?setelah=' + terakhir, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d) { return; }
                    badges.forEach(function (b) {
                        b.textContent = d.belum_dibaca;
                        b.style.display = d.belum_dibaca > 0 ? 'inline-block' : 'none';
                    });
                    d.baru.forEach(function (n) {
                        terakhir = Math.max(terakhir, n.id);
                        tampilToast(n);
                        tambahKeDaftar(n);
                    });
                })
                .catch(function () { /* abaikan error jaringan sesaat */ });
        }

        setInterval(cek, 3000);
    })();
</script>

@if($kunciPush !== '')
<script>
    // Web Push: notifikasi di layar perangkat walau aplikasi sedang ditutup.
    (function () {
        var kunciPublik = "{{ $kunciPush }}";
        var urlDaftar = "{{ route('push.daftar') }}";
        var urlBatal = "{{ route('push.batal') }}";
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var tombol = document.getElementById('btn-push');
        var formKeluar = document.getElementById('form-keluar');

        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            return; // perangkat/browser ini tidak mendukung push
        }

        function kirim(url, isi) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(isi)
            });
        }

        function dataLangganan(sub) {
            var j = sub.toJSON();
            j.contentEncoding = (window.PushManager.supportedContentEncodings || ['aes128gcm'])[0];
            return j;
        }

        function kunciKeBytes(b64) {
            var pad = '='.repeat((4 - b64.length % 4) % 4);
            var teks = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
            var arr = new Uint8Array(teks.length);
            for (var i = 0; i < teks.length; i++) { arr[i] = teks.charCodeAt(i); }
            return arr;
        }

        function aturTombol(aktif) {
            if (!tombol) { return; }
            tombol.style.display = '';
            tombol.textContent = aktif ? 'Matikan notifikasi di perangkat ini' : 'Aktifkan notifikasi di perangkat ini';
        }

        function langgananSekarang() {
            return navigator.serviceWorker.ready.then(function (reg) {
                return reg.pushManager.getSubscription().then(function (sub) {
                    return { reg: reg, sub: sub };
                });
            });
        }

        // Saat halaman dibuka: tampilkan status, dan hubungkan perangkat ini ke akun yang sedang login
        langgananSekarang().then(function (h) {
            aturTombol(!!h.sub);

            if (h.sub && Notification.permission === 'granted') {
                var sudah = false;
                try { sudah = sessionStorage.getItem('push-sinkron') === '1'; } catch (e) {}
                if (!sudah) {
                    kirim(urlDaftar, dataLangganan(h.sub)).then(function () {
                        try { sessionStorage.setItem('push-sinkron', '1'); } catch (e) {}
                    }).catch(function () {});
                }
            }
        });

        if (tombol) {
            tombol.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                langgananSekarang().then(function (h) {
                    if (h.sub) {
                        // Matikan
                        return kirim(urlBatal, { endpoint: h.sub.endpoint })
                            .then(function () { return h.sub.unsubscribe(); })
                            .then(function () { aturTombol(false); });
                    }

                    // Aktifkan
                    return Notification.requestPermission().then(function (izin) {
                        if (izin !== 'granted') {
                            alert('Izin notifikasi tidak diberikan. Aktifkan lewat pengaturan situs di browser, lalu coba lagi.');
                            return;
                        }
                        return h.reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: kunciKeBytes(kunciPublik)
                        }).then(function (baru) {
                            return kirim(urlDaftar, dataLangganan(baru));
                        }).then(function (r) {
                            if (r && r.ok) {
                                aturTombol(true);
                                try { sessionStorage.setItem('push-sinkron', '1'); } catch (e) {}
                            } else {
                                alert('Gagal menyimpan langganan notifikasi di server.');
                            }
                        });
                    });
                }).catch(function (err) {
                    alert('Tidak bisa mengubah notifikasi: ' + err.message);
                });
            });
        }

        // Saat keluar akun: lepaskan perangkat ini dari akun, supaya push tidak nyasar ke pengguna berikutnya
        if (formKeluar) {
            formKeluar.addEventListener('submit', function (e) {
                e.preventDefault();
                try { sessionStorage.removeItem('push-sinkron'); } catch (err) {}
                langgananSekarang().then(function (h) {
                    return h.sub ? kirim(urlBatal, { endpoint: h.sub.endpoint }) : null;
                }).catch(function () {}).then(function () {
                    formKeluar.submit();
                });
            });
        }
    })();
</script>
@endif
@endauth
</body>
</html>
