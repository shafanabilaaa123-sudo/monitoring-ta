# Panduan: Sistem Monitoring Tugas Akhir (Laravel)

Paket ini berisi kode aplikasi yang **ditempel ke project Laravel baru**. Kamu tidak perlu membuat login, database, atau fitur satu per satu: semuanya sudah ada di sini. Target: aplikasi jalan di laptopmu dalam sekitar 20 menit.

> Catatan jujur: kode sudah dicek sintaksnya (semua file PHP lolos `php -l`, tag Blade seimbang, semua JavaScript lolos `node --check`), dan logika service worker untuk push sudah diuji lewat simulasi (10 skenario lulus). Namun **belum pernah dijalankan di Laravel sungguhan**, dan pengiriman push lewat paket `minishlink/web-push` belum diuji, karena Laravel dan paket itu tidak bisa diunduh di lingkungan tempat kode ini dibuat. Kalau ada error saat langkah di bawah, kirim pesan error-nya ke Claude untuk diperbaiki.

## Isi paket

| Bagian | File |
| --- | --- |
| Database (6 migration) | `database/migrations/` |
| Data contoh + akun demo | `database/seeders/DatabaseSeeder.php` |
| Model | `app/Models/` |
| Hak akses per peran | `app/Http/Middleware/CekRole.php` |
| Controller (login, mahasiswa, dosen, admin, notifikasi, dokumen) | `app/Http/Controllers/` |
| Pembuat notifikasi | `app/Services/Notifier.php` |
| Pengingat otomatis (F09) | `app/Console/Commands/IngatkanBimbingan.php`, `routes/console.php` |
| Tampilan (Bootstrap 5) | `resources/views/` |
| PWA (bisa diinstal) | `public/manifest.webmanifest`, `public/sw.js`, `public/offline.html`, `public/icons/` |
| Web Push (notifikasi saat aplikasi tertutup) | `app/Services/WebPushService.php`, `app/Http/Controllers/PushController.php`, `app/Console/Commands/BuatKunciPush.php`, `config/webpush.php`, migration `push_subscriptions` |
| Verifikasi APK (Digital Asset Links) | route `/.well-known/assetlinks.json` di `routes/web.php`, `config/android.php` |
| Route | `routes/web.php` |

## Prasyarat

Install salah satu paket berikut (paling mudah untuk Windows: **Laragon**, sudah berisi PHP, Composer, MySQL, dan Node):

- PHP 8.2 atau lebih baru, dengan ekstensi `pdo_mysql` (atau `pdo_sqlite`), `mbstring`, `openssl`, `fileinfo`, `zip`
- Composer
- Koneksi internet saat aplikasi dibuka (Bootstrap dimuat dari CDN)

## Langkah 1: Buat project Laravel baru

```bash
composer create-project laravel/laravel monitoring-ta
cd monitoring-ta
```

## Langkah 2: Tempel kode dari paket ini

Salin **isi** folder `monitoring-ta-kode` (bukan folder luarnya) ke dalam folder `monitoring-ta`. Kalau ditanya timpa file yang sudah ada, pilih **Replace / Timpa**. File bawaan yang akan tertimpa: `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `routes/console.php`, `database/seeders/DatabaseSeeder.php`.

- Windows (Command Prompt): `xcopy /E /Y "C:\lokasi\monitoring-ta-kode\*" "C:\lokasi\monitoring-ta\"`
- Mac/Linux: `cp -r /lokasi/monitoring-ta-kode/. /lokasi/monitoring-ta/`

## Langkah 3: Pilih database

**Jalur cepat (SQLite, tanpa install MySQL):** tidak perlu mengubah apa pun. Laravel baru sudah memakai SQLite secara bawaan.

**Sesuai proposal (MySQL):** buat database kosong bernama `monitoring_ta` lewat phpMyAdmin/HeidiSQL, lalu ubah file `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monitoring_ta
DB_USERNAME=root
DB_PASSWORD=
```

## Langkah 4: Buat tabel dan isi data contoh

```bash
php artisan migrate:fresh --seed
```

Kalau memakai SQLite dan ditanya apakah file database boleh dibuat, jawab **yes**.

## Langkah 5: Jalankan aplikasi

```bash
php artisan serve
```

Buka http://127.0.0.1:8000 di browser.

## Langkah 6: Coba semua fitur (10 menit)

Semua akun demo berkata sandi `password`.

| Peran | Email |
| --- | --- |
| Admin / koordinator | `admin@kampus.test` |
| Dosen | `dosen1@kampus.test`, `dosen2@kampus.test` |
| Mahasiswa | `mhs1@kampus.test` (sudah punya TA), `mhs2@kampus.test` (menunggu pembimbing), `mhs3@kampus.test` (belum mengajukan) |

Cara mencoba notifikasi real time: buka **dua browser berbeda** (atau satu biasa dan satu jendela incognito), login sebagai dua peran yang berbeda, lalu lakukan alur ini:

1. Login `mhs2` lalu lihat TA yang sudah diajukan. Login `admin`, pilih dosen pembimbing untuk Nur Aisyah, klik **Tetapkan**. Di browser mahasiswa dan dosen, notifikasi muncul sendiri dalam beberapa detik.
2. Login `mhs1`, ajukan jadwal bimbingan. Di browser `dosen1`, muncul notifikasi. Dosen klik **Setujui**, **Ubah waktu**, atau **Tolak**.
3. `dosen1` mengisi **catatan bimbingan + instruksi revisi**. `mhs1` langsung mendapat notifikasi.
4. `mhs1` mengunggah dokumen, lalu memperbarui tahapan. `dosen1` mendapat notifikasi.
5. `dosen1` mengubah tahapan ke **Selesai**. Progres menjadi 100%.

## Langkah 7: Pengingat otomatis (kebutuhan F09)

Pengingat jadwal 24 jam sebelum bimbingan dan peringatan mahasiswa tanpa aktivitas 14 hari dijalankan oleh scheduler. Untuk demo, buka terminal kedua:

```bash
php artisan schedule:work
```

Tes manual satu kali: `php artisan bimbingan:ingatkan`

## Cara kerja notifikasi (penting untuk laporan)

Setiap aksi penting (jadwal, catatan, dokumen, tahapan, penetapan pembimbing, pengingat) disimpan sebagai notifikasi di tabel `notifikasi`, lalu dikirim lewat **dua jalur**:

1. **Aplikasi sedang terbuka:** browser menanyakan server tiap **3 detik** (short polling AJAX), lalu menampilkan toast dan menambah angka lonceng tanpa refresh. Notifikasi tiba paling lama sekitar 3 detik.
2. **Aplikasi sedang ditutup:** server mengirim **Web Push** (standar W3C dengan kunci VAPID, lewat paket `minishlink/web-push`) sehingga notifikasi muncul di layar HP atau laptop. Pengiriman dilakukan setelah respons halaman selesai, jadi tidak memperlambat. Kalau aplikasi sedang terlihat di layar, push tidak ditampilkan lagi agar tidak ganda.

Proposal menyebut **WebSocket** dan **Firebase Cloud Messaging**. Aplikasi ini memakai short polling dan Web Push. Supaya laporan konsisten, ubah kalimat di proposal (Bab Teknologi dan Batasan) menjadi, misalnya: "Notifikasi real time diimplementasikan dengan short polling AJAX untuk aplikasi yang sedang terbuka dan Web Push (VAPID) untuk aplikasi yang sedang tertutup."

## Pengujian untuk laporan

**Black box (isi kolom hasil sendiri):**

| No | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| 1 | Login dengan akun benar | Masuk ke dashboard sesuai peran |
| 2 | Login dengan kata sandi salah | Muncul pesan error, tidak masuk |
| 3 | Mahasiswa buka halaman admin (`/admin/pengguna`) | Ditolak (403) |
| 4 | Mahasiswa mengajukan judul | TA tersimpan, admin dapat notifikasi |
| 5 | Admin menetapkan pembimbing | Mahasiswa dan dosen dapat notifikasi |
| 6 | Mahasiswa mengajukan jadwal | Dosen dapat notifikasi |
| 7 | Dosen setujui / ubah / tolak jadwal | Status berubah, mahasiswa dapat notifikasi |
| 8 | Dosen menyimpan catatan + revisi | Catatan tampil, mahasiswa dapat notifikasi |
| 9 | Mahasiswa unggah dokumen (PDF) | Dokumen tampil dan bisa diunduh, dosen dapat notifikasi |
| 10 | Mahasiswa unggah file bukan jenis yang diizinkan | Ditolak dengan pesan error |
| 11 | Perubahan tahapan | Progress bar berubah, pihak lain dapat notifikasi |
| 12 | `php artisan bimbingan:ingatkan` pada jadwal 24 jam ke depan | Mahasiswa dan dosen dapat pengingat |
| 13 | Klik "Aktifkan notifikasi di perangkat ini" lalu izinkan | Tombol berubah menjadi "Matikan..." |
| 14 | Tutup aplikasi, lalu dosen menyetujui jadwal | Notifikasi muncul di layar perangkat mahasiswa |
| 15 | Ketuk notifikasi di layar | Aplikasi terbuka di halaman yang terkait |
| 16 | Keluar akun lalu login sebagai pengguna lain | Notifikasi akun sebelumnya tidak masuk ke perangkat ini |

**Waktu notifikasi:** buka dua browser, catat waktu saat aksi dilakukan dan saat toast muncul di browser lain (stopwatch ponsel cukup). Ulangi 10 sampai 20 kali dan hitung rata-ratanya. Target di proposal: di bawah 5 detik.

**Kepuasan pengguna:** minta teman atau dosen mencoba aplikasi, lalu isi kuesioner SUS (10 pertanyaan). Skor di atas 68 berarti dapat diterima.

## Deploy ke cloud (agar benar-benar "berbasis cloud")

**Jalur tercepat: `DEPLOY-DOCKER.md`** (semua dikemas dalam Docker, di server cukup `docker compose up -d`). Cara membuat server dan mendapat kredit cloud ada di `DEPLOY.md`. Ringkasan umum di bawah ini hanya pengingat untuk cara manual.

Langkah umum untuk server Ubuntu (VPS) dengan PHP 8.2+, Nginx, MySQL, dan Composer:

1. Unggah project (misalnya lewat Git), lalu `composer install --no-dev --optimize-autoloader`.
2. Salin `.env.example` menjadi `.env`, isi `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domainmu`, dan data MySQL. Jalankan `php artisan key:generate`.
3. `php artisan migrate --force` (jangan pakai `--seed` di produksi; buat akun lewat halaman admin, atau seed sekali lalu **ganti semua kata sandi**).
4. Arahkan root Nginx ke folder `public`, beri izin tulis ke `storage` dan `bootstrap/cache`.
5. Pasang HTTPS dengan Certbot.
6. Tambahkan cron agar pengingat jalan: `* * * * * cd /path/monitoring-ta && php artisan schedule:run >> /dev/null 2>&1`
7. Hapus blok "Akun demo" di `resources/views/auth/login.blade.php`.

## Langkah 8: Aktifkan notifikasi push (muncul walau aplikasi ditutup)

Kode push sudah ada di paket. Yang perlu dilakukan tinggal memasang satu paket dan membuat kunci:

```bash
composer require minishlink/web-push
php artisan migrate
php artisan push:keys
php artisan config:clear
```

`push:keys` membuat kunci VAPID dan menuliskannya sendiri ke `.env`. Setelah itu, di dalam aplikasi:

1. Login, buka menu **Notifikasi** di navbar, klik **Aktifkan notifikasi di perangkat ini**, lalu pilih **Izinkan**.
2. Buka halaman **Lihat semua notifikasi**, klik **Kirim notifikasi uji ke perangkatku**.
3. Tutup aplikasi, lalu lakukan aksi dari akun lain (misalnya dosen menyetujui jadwal). Notifikasi muncul di layar.

Syarat dan batasan:

- Push hanya jalan lewat **HTTPS** (atau `localhost` di laptop yang sama). Di HP, lihat bagian berikutnya.
- **iPhone:** push hanya bekerja jika aplikasi sudah dipasang lewat Safari (Bagikan, lalu Tambah ke Layar Utama, iOS 16.4 atau lebih baru), dan tombol aktifkan ditekan dari aplikasi di layar utama itu.
- **Android 13 ke atas:** izinkan notifikasi saat diminta.
- Notifikasi diikat ke perangkat dan akun yang sedang login. Saat keluar akun, perangkat otomatis dilepas dari akun itu.

## Langkah 9: Buka dari HP lewat HTTPS

HP butuh alamat HTTPS agar bisa memasang aplikasi dan menerima push. Untuk demo cepat tanpa deploy, pakai tunnel dari laptopmu (aplikasi harus tetap jalan dengan `php artisan serve`):

```bash
# pilih salah satu
cloudflared tunnel --url http://localhost:8000
ngrok http 8000
```

Alat itu menampilkan alamat `https://....`. Buka alamat itu di HP. Aplikasi sudah otomatis memakai https untuk semua tautan dan form di belakang tunnel. Alamat tunnel biasanya berubah tiap dijalankan, dan push harus diaktifkan ulang di alamat baru.

Untuk pemakaian sungguhan (dan untuk APK), deploy ke cloud dengan domain tetap (lihat bagian deploy di atas).

## Langkah 10: Pasang sebagai aplikasi

Cara tercepat, tanpa file APK:

| Perangkat | Cara memasang |
| --- | --- |
| Android (Chrome) | Buka alamat HTTPS, menu tiga titik, lalu **Instal aplikasi**. Chrome membuat aplikasi sungguhan dengan ikon di layar utama, dan push tetap jalan. |
| iPhone (Safari) | Tombol Bagikan, lalu **Tambah ke Layar Utama** |
| Laptop (Chrome/Edge) | Klik ikon "Instal" di address bar |

## Langkah 11: Buat file APK (kalau dosen meminta)

APK dibuat dari alamat HTTPS yang **tetap** (bukan alamat tunnel yang berubah-ubah), jadi lakukan setelah aplikasi dideploy. Pakai PWABuilder, gratis dan tanpa Android Studio:

1. Buka **pwabuilder.com**, masukkan alamat aplikasimu, klik **Start**. Perbaiki peringatan merah kalau ada.
2. Pilih **Package for stores**, lalu **Android**.
3. Isi **Package ID** dengan `com.monitoringta.app` (sama dengan nilai bawaan di `config/android.php`). Biarkan opsi notifikasi dalam keadaan aktif. Untuk kunci penanda tangan, pilih **New**.
4. Unduh hasilnya (zip). Isinya APK, file kunci penanda tangan, dan keterangan sidik jari **SHA-256**. **Simpan file kunci itu**, karena dibutuhkan untuk memperbarui aplikasi di kemudian hari.
5. Salin sidik jari SHA-256 itu ke `.env` di server:
   ```
   ANDROID_SHA256=AA:BB:CC:...
   ```
   lalu jalankan `php artisan config:clear`. Pastikan `https://domainmu/.well-known/assetlinks.json` menampilkan JSON (bukan 404).
6. Pasang APK di HP Android (izinkan "instal dari sumber tidak dikenal").

Kalau aplikasi terbuka tetapi ada address bar di bagian atas, artinya verifikasi gagal: periksa sidik jari di `.env` dan Package ID. Notifikasi di APK memakai Chrome di balik layar, jadi push yang sudah diaktifkan di atas ikut bekerja.

## Memasang logo kampus

Aplikasi sudah menyediakan tempat logo di halaman masuk, halaman daftar, dan kepala halaman setelah login. Selama berkasnya belum ada, yang tampil adalah ikon topi toga biasa.

1. Siapkan logo kampus sebagai gambar **PNG persegi** (minimal 256 x 256 piksel, latar transparan lebih bagus). Logo resmi biasanya ada di situs kampus.
2. Simpan dengan nama persis `logo-kampus.png` di folder `public` (`monitoring-ta-kode\public\logo-kampus.png`).
3. Lokal: salin juga ke `monitoring-ta\public`, lalu muat ulang halaman.
4. Cloud: jalankan lagi skrip `siapkan-laptop.ps1` supaya logo terunggah ke GitHub, lalu di server jalankan `cd ~/monitoring-ta && git pull && sudo docker compose up -d --build`.

Nama kampus di halaman masuk bisa diganti lewat `KAMPUS_NAMA=...` di file `.env`.

## Pendaftaran mandiri (mahasiswa dan dosen daftar sendiri)

Di halaman login ada tautan **Daftar** (alamat `/daftar`).

- **Mahasiswa** bebas mendaftar sendiri (nama, email, NIM, kata sandi) dan langsung masuk ke aplikasi.
- **Dosen** harus mengisi **kode pendaftaran** yang kamu tentukan. Tambahkan di file `.env`:
  ```
  KODE_DAFTAR_DOSEN=isi-kode-rahasia-kamu
  ```
  Bagikan kode itu hanya ke dosen. Kalau baris ini dikosongkan atau tidak ada, pilihan "Dosen" tidak muncul di halaman daftar (dosen hanya bisa ditambahkan oleh admin lewat menu Pengguna).
- **Admin** tidak bisa mendaftar sendiri. Admin hanya dibuat oleh admin lain.
- Setiap ada yang mendaftar, admin mendapat notifikasi.
- Untuk mematikan halaman Daftar sama sekali: `PENDAFTARAN_AKTIF=false` di `.env`.

Setelah mengubah `.env`, jalankan `php artisan config:clear`. Tidak ada migration baru untuk fitur ini.

## Kalau ada masalah

| Gejala | Solusi |
| --- | --- |
| `could not find driver` | Aktifkan `pdo_mysql` atau `pdo_sqlite` di `php.ini`, lalu restart. |
| Upload gagal untuk file di atas 2 MB | Di `php.ini` ubah `upload_max_filesize = 10M` dan `post_max_size = 12M`, lalu restart. |
| Halaman tanpa gaya (polos) | Bootstrap dimuat dari internet; pastikan laptop online. |
| `Class "..." not found` atau route tidak dikenali | Jalankan `composer dump-autoload` lalu `php artisan optimize:clear`. |
| Setelah mengubah `.env` tidak berpengaruh | `php artisan config:clear` |
| Mau mengulang dari data awal | `php artisan migrate:fresh --seed` |
| Notifikasi tidak muncul otomatis | Buka konsol browser (F12), cek apakah `/notifikasi/baru` mengembalikan JSON tanpa error. |
| Tombol "Aktifkan notifikasi" tidak muncul | Kunci VAPID belum ada. Jalankan `php artisan push:keys` lalu `php artisan config:clear`. Pastikan juga halaman dibuka lewat HTTPS atau localhost. |
| `composer require minishlink/web-push` meminta ekstensi gmp | Aktifkan `extension=gmp` di `php.ini` lalu restart. |
| Push tidak muncul walau sudah diizinkan | Cek `storage/logs/laravel.log` untuk baris "Web Push". Pastikan jam server benar dan tidak ada pembatasan baterai untuk Chrome di HP. |
| Form gagal di belakang tunnel (mixed content) | Pastikan memakai versi paket ini (sudah memaksa https), lalu `php artisan optimize:clear`. |
| `/.well-known/assetlinks.json` menampilkan 404 | `ANDROID_SHA256` di `.env` masih kosong. Isi, lalu `php artisan config:clear`. |
