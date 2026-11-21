# Deploy Versi Cloud (Docker): satu perintah

Paket ini membungkus aplikasi, database MySQL, dan HTTPS otomatis ke dalam Docker. Di server kamu cukup memasang Docker, mengambil kode, mengisi satu file `.env`, lalu menjalankan satu perintah. Tidak perlu memasang PHP, Nginx, atau MySQL satu per satu.

> Catatan jujur: file `docker-compose.yml` sudah divalidasi, dan skrip serta kode PHP-nya sudah dicek sintaksnya. Tetapi **build image dan menjalankan container belum pernah diuji**, karena Docker tidak bisa dijalankan di tempat paket ini dibuat. Kalau ada langkah yang gagal, kirim pesan error-nya ke Claude, jangan dilewati.

> Tidak punya akun Azure? Pakai jalur gratis lewat GitHub saja: lihat `DEPLOY-RENDER.md`.

## Jalur paling singkat: tiga skrip otomatis

Kalau tidak mau mengetik langkah satu per satu, pakai tiga skrip di folder `deploy`. Kamu hanya menjalankan tiga hal, dan skrip menanyakan apa yang dibutuhkan.

| # | Di mana | Perintah | Apa yang dikerjakan |
| --- | --- | --- | --- |
| 1 | PowerShell laptop | `powershell -ExecutionPolicy Bypass -File .\deploy\siapkan-laptop.ps1` (dari folder `monitoring-ta-kode`) | Menyalin file, memasang paket push, mengunggah kode ke GitHub |
| 2 | Azure Cloud Shell (Bash) | `bash <(curl -fsSL https://raw.githubusercontent.com/USERNAME/monitoring-ta/main/deploy/buat-server-azure.sh)` | Membuat server Ubuntu di Azure, membuka port, memberi alamat gratis |
| 3 | Di dalam server | `bash <(curl -fsSL https://raw.githubusercontent.com/USERNAME/monitoring-ta/main/deploy/siapkan-server.sh)` | Memasang Docker, mengisi pengaturan, menjalankan aplikasi, menunggu HTTPS siap |

Ganti `USERNAME` dengan username GitHub-mu. Skrip nomor 1 mencetak perintah nomor 3 lengkap dengan username-mu. Skrip nomor 2 hanya jalan setelah akun Azure dan kreditnya aktif (`DEPLOY.md` Langkah A). Cloud Shell dibuka dari ikon `>_` di bagian atas portal.azure.com, dan saat pertama kali dibuka Azure memintamu membuat penyimpanan kecil.

Skrip-skrip ini belum pernah dijalankan sungguhan. Kalau ada yang gagal, kirim pesan error-nya ke Claude. Bagian di bawah menjelaskan langkah manual yang sama, berguna kalau skrip bermasalah.

## Yang dikerjakan otomatis saat aplikasi menyala

- Membuat kunci aplikasi dan kunci Web Push (disimpan, tidak berubah saat server dinyalakan ulang)
- Menunggu MySQL siap, lalu membuat semua tabel
- Membuat akun admin pertama dari isi `.env`
- Menjalankan pengingat bimbingan otomatis tiap menit
- HTTPS gratis dengan sertifikat yang diperbarui sendiri (lewat Caddy)
- Menyala kembali sendiri kalau server di-restart

## Syarat

- Server **Ubuntu 24.04** dengan RAM **minimal 2 GB** (MySQL cukup berat), port **22, 80, 443** terbuka. Cara membuat server di Azure, termasuk kredit mahasiswa dan alamat gratis, ada di `DEPLOY.md` Langkah A sampai D.
- Alamat atau domain yang sudah mengarah ke IP server (di Azure: isi **DNS name label** di Public IP).
- Kode sudah ada di GitHub (`DEPLOY.md` Langkah B).

## Langkah 1: Siapkan kode di laptop

Ekstrak zip terbaru ke folder `monitoring-ta-kode` (pilih **Replace** kalau ditanya). Lalu salin ke project `monitoring-ta` (folder yang berisi `artisan`) dan unggah ke GitHub:

```powershell
cd "D:\semester 5\proyek inovasi produk\monitoring-ta-kode"
robocopy . monitoring-ta /E /XD monitoring-ta
cd monitoring-ta
git add .
git commit -m "Versi cloud (Docker)"
git push
```

Setelah `robocopy`, pastikan di folder `monitoring-ta` ada `Dockerfile`, `docker-compose.yml`, `Caddyfile`, `.env.cloud.example`, dan folder `docker`. Pastikan juga `composer.json` sudah memuat paket push (`composer require minishlink/web-push` sudah pernah dijalankan). Kalau repository belum dibuat, ikuti `DEPLOY.md` Langkah B.

## Langkah 2: Pasang Docker di server

Masuk ke server lewat SSH, lalu:

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
exit
```

Masuk SSH lagi (supaya izin Docker berlaku), lalu cek: `docker compose version` harus menampilkan versi.

## Langkah 3: Ambil kode dan isi pengaturan

```bash
git clone https://github.com/USERNAME-KAMU/monitoring-ta.git
cd monitoring-ta
cp .env.cloud.example .env
nano .env
```

Isi minimal ini (simpan di nano: **Ctrl+O**, Enter, **Ctrl+X**):

| Baris | Isi |
| --- | --- |
| `DOMAIN` | Alamat servermu **tanpa** `https://`, misalnya `monitoringta-namamu.southeastasia.cloudapp.azure.com` |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | Dua kata sandi database karanganmu sendiri |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Email dan kata sandi admin pertama (kata sandi minimal 8 karakter) |
| `KODE_DAFTAR_DOSEN` | Kode rahasia agar dosen bisa daftar sendiri. Kosongkan kalau dosen hanya ditambah admin. |

Pakai huruf dan angka saja untuk kata sandi, karena karakter seperti `$`, `#`, dan tanda kutip bisa membuat pembacaan file gagal.

## Langkah 4: Jalankan

```bash
docker compose up -d --build
```

Pertama kali butuh sekitar 5 sampai 10 menit karena mengunduh dan membangun image. Pantau dengan:

```bash
docker compose ps
docker compose logs -f app
```

Tanda aplikasi siap: di log muncul baris `[init] Menjalankan migrasi database...` lalu Apache menyala tanpa error. Tekan **Ctrl+C** untuk keluar dari tampilan log (aplikasi tetap berjalan).

## Langkah 5: Buka dan pakai

Buka `https://ALAMAT-SERVERMU`. Sertifikat HTTPS biasanya siap dalam sekitar satu menit setelah container menyala. Login dengan `ADMIN_EMAIL` dan `ADMIN_PASSWORD` tadi.

Lalu:

1. Dosen dan mahasiswa daftar lewat halaman **Daftar**, atau ditambah admin lewat menu **Pengguna**.
2. Di HP, buka alamat itu di Chrome, klik **Notifikasi**, lalu **Aktifkan notifikasi di perangkat ini**.
3. Untuk APK, ikuti Langkah 11 di `PANDUAN.md`. Setelah mendapat sidik jari SHA-256, tambahkan di `.env` server baris `ANDROID_SHA256=...`, lalu jalankan `docker compose up -d`.

## Uji dulu di laptop (opsional)

Kalau laptopmu punya Docker Desktop, kamu bisa mencoba seluruh paket sebelum ke server. Di folder project, salin `.env.cloud.example` menjadi `.env` dengan `DOMAIN=localhost`, jalankan `docker compose up -d --build`, lalu buka `https://localhost`. Browser akan memperingatkan sertifikat tidak tepercaya, lanjutkan saja (itu normal untuk uji lokal).

Perhatian: di laptop, file `.env` bawaan Laravel yang dipakai `php artisan serve` bentrok dengan `.env` Docker. Pakai salah satu saja pada satu waktu.

## Perintah sehari-hari

| Keperluan | Perintah |
| --- | --- |
| Lihat status | `docker compose ps` |
| Lihat log aplikasi | `docker compose logs -f app` |
| Restart aplikasi | `docker compose restart app` |
| Setelah mengubah `.env` | `docker compose up -d` |
| Memperbarui kode | `git pull` lalu `docker compose up -d --build` |
| Cadangan database | `docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" monitoring_ta' > cadangan.sql` |
| Menghentikan (data tetap aman) | `docker compose down` |
| **Menghapus semua termasuk data** | `docker compose down -v` (hati-hati, tidak bisa dibatalkan) |

Data database ada di volume `db_data`, dan berkas unggahan mahasiswa ada di volume `storage_data`. Keduanya tetap ada saat container dibuat ulang atau kode diperbarui.

## Masalah yang sering muncul

| Gejala | Penyebab dan solusi |
| --- | --- |
| Pesan "Isi DOMAIN di file .env" saat `up` | `.env` belum dibuat atau barisnya kosong. Ulangi Langkah 3. |
| Halaman tidak bisa dibuka, atau sertifikat gagal | Port 80 dan 443 belum dibuka di jaringan Azure, atau `DOMAIN` belum mengarah ke IP server. Cek `docker compose logs caddy`. |
| `app` berulang kali restart | Lihat `docker compose logs app`. Penyebab umum: sandi DB di `.env` diubah setelah database pertama kali dibuat. Database menyimpan sandi yang lama, jadi kembalikan sandinya, atau hapus volume database dengan `docker compose down -v` (menghapus semua data). |
| "Migrasi gagal" | MySQL belum sempat siap atau kehabisan memori. Cek `docker compose logs db`, dan pastikan RAM server minimal 2 GB. |
| Build gagal saat `composer install` | Pastikan `composer.json` dan `composer.lock` ikut ter-commit ke GitHub. |
| Error `exec format error` atau `bad interpreter` pada entrypoint | Berkas `docker/entrypoint.sh` berakhir baris gaya Windows. Dockerfile sudah memperbaikinya otomatis, tapi kalau muncul, jalankan `git config core.autocrlf false` lalu commit ulang. |
| Tombol notifikasi tidak muncul | Paket `minishlink/web-push` belum ada di `composer.json`. Jalankan `composer require minishlink/web-push` di laptop, commit, push, lalu `git pull` dan `docker compose up -d --build` di server. |
| Lupa kata sandi admin | Tambah admin baru: `docker compose exec app php artisan admin:buat email-baru@contoh.com` (kata sandi ditanya di layar). |

## Keamanan dasar

- Jangan membagikan isi `.env`, dan jangan mengunggahnya ke GitHub (sudah otomatis diabaikan).
- Server produksi tidak pernah membuat akun demo `password`, dan daftar akun demo disembunyikan di halaman login.
- Pilih kata sandi admin yang kuat, dan bagikan `KODE_DAFTAR_DOSEN` hanya ke dosen.
- Pantau sisa kredit cloud, dan matikan atau hapus server kalau sudah tidak dipakai.
