# Deploy ke Cloud: dari nol sampai bisa dibuka siapa saja

Panduan ini untuk kamu yang belum punya server dan belum punya domain. Hasil akhirnya: aplikasi online di alamat `https://...` yang bisa dibuka siapa saja, notifikasi push berjalan, dan siap dibungkus jadi APK.

> Catatan jujur: langkah dan perintah di sini disusun dari cara standar memasang Laravel di Ubuntu, tetapi **belum pernah dijalankan di server sungguhan** oleh pembuatnya. Tampilan menu Azure juga bisa berubah dari yang tertulis. Kalau ada langkah yang gagal, kirim pesan error-nya ke Claude, jangan dilewati.

Perkiraan waktu: 1 sampai 3 jam, di luar waktu menunggu persetujuan akun.

> **Jalur yang lebih cepat dan otomatis: lihat `DEPLOY-DOCKER.md`.** Di sana semua langkah 4 sampai 7 di bawah (PHP, Nginx, MySQL, HTTPS, migrasi, admin, pengingat) sudah dikemas dalam Docker, jadi di server cukup `docker compose up -d`. Dokumen ini tetap berguna untuk Langkah A sampai D (kredit cloud, GitHub, membuat server) dan sebagai cara manual cadangan.

## Gambaran besar

1. Dapatkan kredit cloud gratis untuk mahasiswa (daftar sekarang, karena persetujuan butuh waktu).
2. Taruh kode di GitHub.
3. Buat server (mesin virtual Ubuntu) di Azure.
4. Pasang PHP, Nginx, dan MySQL di server.
5. Ambil kode ke server, atur `.env`, jalankan migrasi.
6. Pasang HTTPS gratis (Let's Encrypt).
7. Buat akun admin, aktifkan notifikasi push.

Langkah 4 sampai 7 sama untuk penyedia server mana pun yang memakai Ubuntu 24.04, jadi kalau nanti kamu pindah ke VPS lain, hanya langkah 3 yang berbeda.

---

## Langkah A: Dapatkan kredit cloud gratis (lakukan dulu)

1. Buka **education.github.com/pack** dan daftar **GitHub Student Developer Pack** dengan akun GitHub-mu. Siapkan bukti status mahasiswa (kartu mahasiswa atau email kampus).
2. Berdasarkan halaman resminya, paket ini mencantumkan **kredit Microsoft Azure $100** untuk mahasiswa berusia 18 tahun ke atas, serta **domain gratis setahun** (misalnya dari Namecheap, .TECH, atau Name.com). Syarat dan jumlah bisa berubah, jadi baca halaman penawarannya saat mendaftar.
3. Persetujuan tidak instan dan bisa makan waktu. Kalau belum disetujui sampai tenggat dekat, lihat bagian "Kalau kredit belum disetujui" di akhir.

Domain tidak wajib. Azure memberi alamat gratis untuk server (lihat Langkah C), dan alamat itu sudah cukup untuk HTTPS.

## Langkah B: Taruh kode di GitHub

Di laptop, **pastikan dulu paket push sudah tercatat di project** (kalau belum pernah menjalankannya):

```powershell
cd "D:\semester 5\proyek inovasi produk\monitoring-ta-kode\monitoring-ta"
composer require minishlink/web-push
```

Lalu buat repository di github.com (klik **New**, nama `monitoring-ta`, pilih **Public** supaya server mudah mengambilnya). File `.env` berisi rahasia dan **otomatis tidak ikut** diunggah karena sudah diabaikan Laravel. Kemudian:

```powershell
git init
git add .
git commit -m "Monitoring TA"
git branch -M main
git remote add origin https://github.com/USERNAME-KAMU/monitoring-ta.git
git push -u origin main
```

Ganti `USERNAME-KAMU` dengan username GitHub-mu. Kalau Git belum terpasang, install dari git-scm.com.

## Langkah C: Buat server di Azure

Di portal.azure.com, buat **Virtual machine**. Menu bisa sedikit berbeda, yang penting nilainya:

| Pengaturan | Nilai |
| --- | --- |
| Image | **Ubuntu Server 24.04 LTS** (jangan 22.04, PHP-nya terlalu lama untuk Laravel terbaru) |
| Region | **Southeast Asia** (Singapura), paling dekat dengan Indonesia |
| Ukuran | Yang paling murah dengan RAM **minimal 1 GB**. RAM 2 GB lebih aman saat `composer install`. |
| Autentikasi | Kata sandi (paling mudah untuk pemula). Catat nama pengguna dan kata sandinya. |
| Port masuk yang dibuka | **SSH (22), HTTP (80), HTTPS (443)** |

Setelah server jadi:

1. Buka **Public IP address** milik server itu, lalu isi kolom **DNS name label** (misalnya `monitoringta-namamu`). Azure lalu memberi alamat gratis seperti `monitoringta-namamu.southeastasia.cloudapp.azure.com`. **Catat alamat ini, itu domainmu.**
2. Disarankan: ubah IP menjadi **Static** di halaman yang sama supaya tidak berganti.

Kalau kamu punya domain sendiri, arahkan record **A** domain itu ke IP server, dan pakai domain itu di semua langkah di bawah sebagai ganti alamat Azure.

Awasi sisa kredit di menu **Cost Management** supaya tidak habis tanpa sadar, dan **matikan atau hapus server** kalau sudah tidak dipakai.

## Langkah D: Masuk ke server

Di PowerShell laptop (ganti nama pengguna dan alamat):

```powershell
ssh NAMAPENGGUNA@monitoringta-namamu.southeastasia.cloudapp.azure.com
```

Ketik `yes` saat ditanya, lalu masukkan kata sandi server. Semua perintah di Langkah E sampai K dijalankan **di dalam server** ini.

## Langkah E: Pasang software

```bash
sudo apt update && sudo apt -y upgrade
sudo apt -y install nginx mysql-server git unzip composer certbot python3-certbot-nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-gmp php8.3-intl
```

Naikkan batas unggah berkas ke 10 MB, lalu muat ulang PHP:

```bash
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 10M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/^post_max_size.*/post_max_size = 12M/' /etc/php/8.3/fpm/php.ini
sudo systemctl restart php8.3-fpm
```

**Hanya kalau RAM server 1 GB**, tambahkan swap supaya `composer install` tidak gagal kehabisan memori:

```bash
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## Langkah F: Siapkan database MySQL

Ganti `KATASANDI-DB` dengan kata sandi karanganmu sendiri (simpan, dipakai di `.env`):

```bash
sudo mysql -e "CREATE DATABASE monitoring_ta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'ta'@'localhost' IDENTIFIED BY 'KATASANDI-DB';"
sudo mysql -e "GRANT ALL PRIVILEGES ON monitoring_ta.* TO 'ta'@'localhost'; FLUSH PRIVILEGES;"
```

## Langkah G: Ambil kode ke server

```bash
sudo mkdir -p /var/www && sudo chown $USER:$USER /var/www
cd /var/www
git clone https://github.com/USERNAME-KAMU/monitoring-ta.git
cd monitoring-ta
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate --force
```

## Langkah H: Atur `.env` untuk produksi

```bash
nano .env
```

Ubah (atau tambahkan) baris-baris ini. Ganti alamatnya dengan alamatmu dan kata sandi DB dengan yang tadi:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://monitoringta-namamu.southeastasia.cloudapp.azure.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monitoring_ta
DB_USERNAME=ta
DB_PASSWORD=KATASANDI-DB

KODE_DAFTAR_DOSEN=isi-kode-rahasia-untuk-dosen
```

Simpan di nano: **Ctrl+O**, Enter, lalu **Ctrl+X**.

Pastikan `APP_ENV=production`. Dengan itu, akun demo (kata sandi `password`) tidak akan pernah dibuat di server, dan blok "Akun demo" di halaman login tersembunyi.

Buat kunci push, lalu atur izin folder dan jalankan migrasi. **Jangan memakai `--seed`** di server.

```bash
php artisan push:keys

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
```

Mulai sini, semua perintah `php artisan` lain dijalankan dengan awalan `sudo -u www-data` supaya file log dan cache dimiliki pengguna yang benar.

## Langkah I: Atur Nginx

Ganti `ALAMAT-SERVERMU` di baris `server_name` dengan alamat Azure-mu (atau domainmu), lalu jalankan seluruh blok ini:

```bash
sudo tee /etc/nginx/sites-available/monitoring-ta > /dev/null <<'EOF'
server {
    listen 80;
    server_name ALAMAT-SERVERMU;
    root /var/www/monitoring-ta/public;

    index index.php;
    charset utf-8;
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/monitoring-ta /etc/nginx/sites-enabled/monitoring-ta
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

`nginx -t` harus menampilkan `syntax is ok`. Buka `http://ALAMAT-SERVERMU` di browser. Halaman login sudah harus muncul (masih tanpa HTTPS).

## Langkah J: Pasang HTTPS

```bash
sudo certbot --nginx -d ALAMAT-SERVERMU
```

Isi email-mu, setujui syarat, dan pilih opsi untuk **mengarahkan semua HTTP ke HTTPS** kalau ditanya. Pembaruan sertifikat berjalan otomatis. Setelah itu buka `https://ALAMAT-SERVERMU`.

Kalau certbot gagal, hampir selalu karena port 80 atau 443 belum dibuka di pengaturan jaringan Azure, atau alamat belum mengarah ke server.

## Langkah K: Pengingat otomatis, admin, dan push

Pasang cron supaya pengingat bimbingan berjalan:

```bash
echo "* * * * * www-data cd /var/www/monitoring-ta && php artisan schedule:run >> /dev/null 2>&1" | sudo tee /etc/cron.d/monitoring-ta
```

Buat akun admin pertama (kamu akan diminta mengisi kata sandi, minimal 8 karakter):

```bash
sudo -u www-data php artisan admin:buat emailkamu@gmail.com --nama="Nama Kamu"
```

Selesai. Buka `https://ALAMAT-SERVERMU`, login sebagai admin tadi, lalu:

1. Dosen dan mahasiswa bisa daftar sendiri lewat halaman **Daftar** (dosen memakai `KODE_DAFTAR_DOSEN`), atau ditambah admin lewat menu **Pengguna**.
2. Di HP, buka alamat itu di Chrome, klik **Notifikasi**, lalu **Aktifkan notifikasi di perangkat ini**.
3. Untuk APK, lanjut ke Langkah 11 di `PANDUAN.md`. Alamat Azure itu sudah tetap, jadi cocok dipakai PWABuilder.

---

## Memperbarui aplikasi nanti

Di laptop: ubah kode, lalu `git add .`, `git commit -m "pesan"`, `git push`. Di server:

```bash
cd /var/www/monitoring-ta
git pull
composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
```

## Masalah yang sering muncul

| Gejala | Penyebab dan solusi |
| --- | --- |
| Halaman **502 Bad Gateway** | PHP-FPM belum jalan atau nama soket salah. Cek `sudo systemctl status php8.3-fpm`. |
| Halaman **500 Server Error** tanpa detail | Lihat `sudo tail -50 /var/www/monitoring-ta/storage/logs/laravel.log`. Penyebab umum: izin folder (ulangi `chown`/`chmod` di Langkah H) atau isi `.env` salah. |
| `composer install` mati dengan "Killed" | Kehabisan memori. Pasang swap (Langkah E). |
| Certbot gagal | Buka port 80 dan 443 di pengaturan jaringan Azure, dan pastikan alamat server benar. |
| Tidak bisa login padahal admin sudah dibuat | Pastikan `APP_URL` memakai `https://`, lalu `sudo -u www-data php artisan optimize:clear`. |
| Unggah berkas besar gagal | Ulangi pengaturan batas unggah di Langkah E dan `client_max_body_size` di Nginx. |
| Tombol notifikasi tidak muncul | Kunci push belum ada. Jalankan `php artisan push:keys` sebagai pengguna biasa (bukan sudo), lalu `sudo -u www-data php artisan optimize:clear`. |
| Pengingat tidak terkirim | Cek cron: `cat /etc/cron.d/monitoring-ta`, dan pastikan `schedule:run` bisa dijalankan manual. |

## Kalau kredit belum disetujui

Kamu tetap bisa memakai panduan ini di penyedia VPS Ubuntu 24.04 mana pun: hanya Langkah C yang berbeda (cara membuat server dan mendapat alamat atau domain). Cek harga dan promo mahasiswa di penyedia yang kamu pilih, atau tanyakan ke kampus apakah ada server atau kredit cloud untuk tugas PBL. Untuk demo mendadak sebelum server siap, pakai tunnel (Langkah 9 di `PANDUAN.md`) sebagai cadangan.

## Keamanan dasar sebelum dibuka ke publik

- Jangan pernah menjalankan `migrate:fresh --seed` di server. Perintah itu menghapus semua data.
- Jangan membagikan isi `.env` dan jangan mengunggahnya ke GitHub.
- Pilih kata sandi admin yang kuat, dan bagikan `KODE_DAFTAR_DOSEN` hanya ke dosen.
- Kalau server sudah tidak dipakai, matikan atau hapus supaya kredit tidak habis.
