# Deploy gratis tanpa Azure: Render + Neon (cukup akun GitHub)

Aplikasi berjalan di **Render** (hosting gratis dari Dockerfile di repository), dan datanya disimpan di **Neon** (database PostgreSQL gratis). Keduanya bisa dimasuki dengan akun GitHub dan tidak meminta kartu kredit.

> Catatan jujur: paket ini belum pernah dijalankan di Render atau Neon sungguhan. Kalau ada langkah yang gagal, kirim pesan error-nya ke Claude, jangan dilewati.

## Batasan paket gratis (baca dulu)

| Hal | Akibatnya |
| --- | --- |
| Server "tidur" setelah sekitar 15 menit tidak dibuka | Buka pertama berikutnya lambat, sekitar 1 menit. Setelah itu normal. |
| Pengingat bimbingan otomatis berjalan hanya saat server bangun | Pengingat bisa terlambat kalau tidak ada yang membuka aplikasi. |
| Render gratis tidak punya penyimpanan tetap | **Dokumen yang diunggah mahasiswa hilang** kalau server dimulai ulang. Data lain (akun, jadwal, catatan, progres, notifikasi) aman karena ada di Neon. |
| Database Neon gratis punya batas kapasitas | Lebih dari cukup untuk satu program studi. |

Untuk dokumen, minta mahasiswa menempelkan tautan Google Drive di kolom catatan, atau pakai langkah lain yang lebih permanen (server berbayar atau Azure).

## Langkah 1: Unggah kode terbaru ke GitHub

Di PowerShell, dari folder `monitoring-ta-kode`:

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy\siapkan-laptop.ps1
```

Saat ditanya username isi `shafanabilaaa123-sudo`, dan tekan Enter saat diminta membuat repository (sudah ada). Setelah selesai, pastikan di GitHub ada file `render.yaml`.

## Langkah 2: Buat database di Neon

1. Buka **neon.tech**, klik **Sign up**, lalu **Continue with GitHub**.
2. Buat project baru. Nama bebas (misalnya `monitoring-ta`), dan pilih wilayah **Singapore** kalau ada.
3. Di halaman project, cari **Connection string** (**Connect**). **Matikan** pilihan **Connection pooling** supaya yang tampil alamat langsung.
4. Salin alamatnya. Bentuknya seperti:
   `postgresql://namauser:sandi@ep-xxxx.ap-southeast-1.aws.neon.tech/neondb?sslmode=require`

Simpan alamat itu, dan jangan dibagikan ke siapa pun.

## Langkah 3: Ambil kunci notifikasi dari laptop

Buka file `.env` di folder `monitoring-ta` (project di laptopmu). Salin dua baris ini:

```
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
```

Kalau barisnya belum ada, jalankan dulu `php artisan push:keys` di folder `monitoring-ta`. Kunci ini harus tetap sama selamanya. Kalau berubah, semua langganan notifikasi lama tidak berlaku.

## Langkah 4: Buat layanan di Render

1. Buka **render.com**, klik **Get Started**, lalu **GitHub**, dan izinkan akses ke repository `monitoring-ta`.
2. Klik **New +**, lalu **Blueprint**. Pilih repository `monitoring-ta`. Render membaca `render.yaml` sendiri.
3. Render meminta kamu mengisi nilai berikut:

| Nama | Isi |
| --- | --- |
| `DB_URL` | Alamat koneksi Neon dari Langkah 2 |
| `ADMIN_EMAIL` | Email admin pertama |
| `ADMIN_PASSWORD` | Kata sandi admin (minimal 8 karakter, huruf dan angka saja) |
| `KODE_DAFTAR_DOSEN` | Kode rahasia untuk dosen mendaftar. Boleh dikosongkan. |
| `VAPID_PUBLIC_KEY` | Dari Langkah 3 (hanya nilainya, tanpa tulisan `VAPID_PUBLIC_KEY=`) |
| `VAPID_PRIVATE_KEY` | Dari Langkah 3 (hanya nilainya) |

4. Klik **Apply**, dan tunggu 5 sampai 10 menit sampai statusnya **Live**.
5. Alamat aplikasinya tampil di halaman layanan, bentuknya `https://monitoring-ta-xxxx.onrender.com`. Buka alamat itu dan login dengan admin tadi.

**Kalau Blueprint tidak bisa dipakai** (misalnya diminta kartu), buat manual: **New + → Web Service**, pilih repository, **Language: Docker**, **Instance Type: Free**, **Region: Singapore**. Di **Environment** tambahkan: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `DB_CONNECTION=pgsql`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `APP_KEY_BASE=` (isi teks acak panjang apa saja), ditambah semua nama di tabel atas. Di **Health Check Path** isi `/login`.

## Langkah 5: Pakai

- Buka alamat `https://...onrender.com` di HP (Chrome), lalu **Install app**.
- Dosen dan mahasiswa mendaftar sendiri lewat halaman **Daftar**.
- Untuk notifikasi saat aplikasi ditutup: **Notifikasi → Aktifkan notifikasi di perangkat ini**.
- Untuk APK, ikuti Langkah 11 di `PANDUAN.md` memakai alamat Render itu. Isi `ANDROID_SHA256` di tab **Environment** Render, lalu simpan.

## Salinan (cadangan) database

Ada tiga cara menyalin data:

1. **Dari aplikasi:** login sebagai admin, buka beranda admin, lalu klik **Unduh salinan data (JSON)**. Hasilnya satu berkas berisi semua akun, tugas akhir, jadwal, catatan, dokumen (datanya saja, bukan berkas yang diunggah), dan notifikasi.
2. **Dari laptop:** `php artisan data:ekspor` di folder `monitoring-ta` menyimpan salinan data lokal ke `storage/app/cadangan-TANGGAL.json`.
3. **Memulihkan:** `php artisan data:impor berkas.json` **mengganti seluruh data** dengan isi berkas. Berkas itu harus berasal dari salah satu cara di atas.

Menyalin data yang sudah kamu isi di laptop ke cloud:
1. Di laptop, jalankan `php artisan data:ekspor cadangan.json`.
2. Buka `.env` di `monitoring-ta`, ubah sementara `DB_CONNECTION=pgsql` dan tambahkan `DB_URL=` (alamat Neon). Ekstensi PHP `pdo_pgsql` harus aktif (hapus tanda `;` di depan `extension=pdo_pgsql` pada `php.ini`).
3. Jalankan `php artisan migrate --force` lalu `php artisan data:impor cadangan.json`.
4. Kembalikan `.env` seperti semula.

Simpan berkas cadangan dengan aman. Kata sandi di dalamnya sudah berbentuk hash, tapi isinya tetap data pribadi.

## Masalah yang sering muncul

| Gejala | Penyebab dan solusi |
| --- | --- |
| Build gagal di Render | Buka tab **Logs**, lalu kirim 30 baris terakhir ke Claude. |
| Halaman "Application error" atau 500 | Biasanya `DB_URL` salah. Pastikan alamatnya lengkap (termasuk `?sslmode=require`) dan tanpa spasi. |
| Log menulis "Migrasi gagal" | Cek `DB_URL` di Neon dan di Render. Pastikan project Neon belum ditidurkan (buka dashboard Neon sekali). |
| Notifikasi tidak muncul | Pastikan `VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY` terisi, lalu aktifkan lagi tombol notifikasi di HP. |
| Harus login ulang setiap hari | Wajar kalau server dimulai ulang, dan seharusnya jarang. Pastikan `APP_KEY_BASE` ada dan tidak diubah. |
