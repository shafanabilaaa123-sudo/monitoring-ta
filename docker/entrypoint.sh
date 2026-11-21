#!/bin/bash
# Dijalankan setiap container aplikasi menyala.
# Mengurus semua yang biasanya dikerjakan manual: kunci, migrasi, admin pertama, pengingat.
set -e
cd /var/www/html

# Jalankan perintah artisan sebagai www-data supaya berkas log/cache tidak dimiliki root
art() { runuser -u www-data -- php artisan "$@"; }

if [ "$1" = "apache2-foreground" ]; then
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
             storage/logs storage/app/public bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

    # 0. Penyesuaian untuk hosting seperti Render (opsional, diabaikan kalau variabelnya tidak ada)
    #    - Apache mendengarkan di port yang diminta hosting (PORT)
    #    - APP_URL diambil dari alamat publik yang diberikan hosting
    #    - DATABASE_URL dipakai bersama DB_URL
    #    - APP_KEY dibuat tetap dari APP_KEY_BASE, supaya login tidak hilang saat server dihidupkan ulang
    if [ -n "${PORT:-}" ] && [ "$PORT" != "80" ]; then
        sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
        sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
    fi
    if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
        export APP_URL="$RENDER_EXTERNAL_URL"
    fi
    if [ -n "${DB_URL:-}" ] && [ -z "${DATABASE_URL:-}" ]; then
        export DATABASE_URL="$DB_URL"
    fi
    if [ -z "${APP_KEY:-}" ] && [ -n "${APP_KEY_BASE:-}" ]; then
        export APP_KEY="$(php -r 'echo "base64:" . base64_encode(hash("sha256", getenv("APP_KEY_BASE"), true));')"
    fi

    # 1. APP_KEY: dibuat sekali, disimpan di volume agar tidak berubah saat container dibuat ulang
    if [ -z "${APP_KEY:-}" ]; then
        if [ ! -s storage/.app_key ]; then
            php -r 'echo "base64:" . base64_encode(random_bytes(32));' > storage/.app_key
            echo "[init] APP_KEY baru dibuat."
        fi
        export APP_KEY="$(cat storage/.app_key)"
    fi

    # 2. Kunci VAPID untuk Web Push: dibuat sekali dan disimpan di volume
    if [ -z "${VAPID_PUBLIC_KEY:-}" ]; then
        if [ ! -s storage/.vapid ]; then
            if php -r 'require "vendor/autoload.php"; $k = Minishlink\WebPush\VAPID::createVAPIDKeys(); echo $k["publicKey"] . "\n" . $k["privateKey"];' > storage/.vapid 2>/dev/null; then
                echo "[init] Kunci VAPID baru dibuat."
            else
                rm -f storage/.vapid
                echo "[init] Paket minishlink/web-push tidak ditemukan. Notifikasi push dimatikan (notifikasi dalam aplikasi tetap jalan)."
            fi
        fi
        if [ -s storage/.vapid ]; then
            export VAPID_PUBLIC_KEY="$(sed -n 1p storage/.vapid)"
            export VAPID_PRIVATE_KEY="$(sed -n 2p storage/.vapid)"
        fi
    fi

    # 3. Migrasi database (menunggu MySQL siap, maksimal sekitar 2 menit)
    echo "[init] Menjalankan migrasi database..."
    for i in $(seq 1 40); do
        if art migrate --force; then
            break
        fi
        if [ "$i" = "40" ]; then
            echo "[init] Migrasi gagal. Periksa pengaturan DB di .env."
            exit 1
        fi
        echo "[init] Database belum siap, coba lagi ($i/40)..."
        sleep 3
    done

    # 4. Admin pertama (hanya dibuat kalau emailnya belum ada)
    if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
        art admin:buat "$ADMIN_EMAIL" --nama="${ADMIN_NAME:-Admin}" --sandi="$ADMIN_PASSWORD" || true
    fi

    # 5. Cache untuk produksi (tanpa route:cache karena ada route berbentuk closure)
    art config:cache || true
    art view:cache || true

    # 6. Pengingat bimbingan otomatis: scheduler dijalankan tiap menit
    (
        while true; do
            runuser -u www-data -- php artisan schedule:run > /dev/null 2>&1 || true
            sleep 60
        done
    ) &
fi

exec "$@"
