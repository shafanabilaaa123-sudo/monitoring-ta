<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PushController;
use App\Http\Middleware\CekRole;
use Illuminate\Support\Facades\Route;

// Verifikasi Digital Asset Links agar APK (Trusted Web Activity) tampil layar penuh tanpa address bar.
// Isi ANDROID_SHA256 di .env dengan sidik jari yang diberikan PWABuilder.
Route::get('/.well-known/assetlinks.json', function () {
    $sidik = array_values(array_filter(array_map('trim', explode(',', (string) config('android.sha256')))));
    abort_if(empty($sidik), 404);

    return response()->json([[
        'relation' => ['delegate_permission/common.handle_all_urls'],
        'target' => [
            'namespace' => 'android_app',
            'package_name' => config('android.package'),
            'sha256_cert_fingerprints' => $sidik,
        ],
    ]]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');

    // Pendaftaran mandiri (mahasiswa bebas, dosen perlu kode)
    Route::get('/daftar', [AuthController::class, 'formDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->middleware('throttle:10,1')->name('daftar.proses');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ta/{ta}', [DashboardController::class, 'detail'])->name('ta.detail');
    Route::get('/dokumen/{dokumen}', [DokumenController::class, 'unduh'])->name('dokumen.unduh');

    // Notifikasi
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/baru', [NotifikasiController::class, 'baru'])->name('notifikasi.baru');
    Route::get('/notifikasi/{notifikasi}/buka', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');

    // Web Push (notifikasi saat aplikasi tertutup)
    Route::post('/push/daftar', [PushController::class, 'daftar'])->name('push.daftar');
    Route::post('/push/batal', [PushController::class, 'batal'])->name('push.batal');
    Route::post('/push/uji', [PushController::class, 'uji'])->name('push.uji');

    // Mahasiswa
    Route::middleware(CekRole::class . ':mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::post('/ta', [MahasiswaController::class, 'ajukanJudul'])->name('ta');
        Route::post('/jadwal', [MahasiswaController::class, 'ajukanJadwal'])->name('jadwal');
        Route::post('/dokumen', [MahasiswaController::class, 'unggahDokumen'])->name('dokumen');
        Route::post('/tahapan', [MahasiswaController::class, 'ubahTahapan'])->name('tahapan');
    });

    // Dosen pembimbing
    Route::middleware(CekRole::class . ':dosen')->prefix('dosen')->name('dosen.')->group(function () {
        Route::patch('/jadwal/{jadwal}', [DosenController::class, 'keputusanJadwal'])->name('jadwal');
        Route::post('/ta/{ta}/catatan', [DosenController::class, 'simpanCatatan'])->name('catatan');
        Route::post('/ta/{ta}/tahapan', [DosenController::class, 'ubahTahapan'])->name('tahapan');
    });

    // Admin / koordinator
    Route::middleware(CekRole::class . ':admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengguna', [AdminController::class, 'pengguna'])->name('pengguna');
        Route::get('/cadangan', [AdminController::class, 'cadangan'])->name('cadangan');
        Route::post('/pengguna', [AdminController::class, 'simpanPengguna'])->name('pengguna.simpan');
        Route::delete('/pengguna/{pengguna}', [AdminController::class, 'hapusPengguna'])->name('pengguna.hapus');
        Route::post('/ta/{ta}/pembimbing', [AdminController::class, 'tetapkanPembimbing'])->name('pembimbing');
    });
});
