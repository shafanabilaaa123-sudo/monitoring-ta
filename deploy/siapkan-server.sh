#!/bin/bash
# Memasang Monitoring TA di server Ubuntu 24.04 secara otomatis (Docker + MySQL + HTTPS).
#
# Cara pakai, di dalam server:
#   bash <(curl -fsSL https://raw.githubusercontent.com/USERNAME/monitoring-ta/main/deploy/siapkan-server.sh)
#
# Skrip akan menanyakan beberapa hal, lalu mengerjakan sisanya sendiri.
set -euo pipefail

tanya() {
    local jawab
    read -r -p "$1" jawab </dev/tty
    echo "$jawab"
}

tanya_rahasia() {
    local jawab
    read -r -s -p "$1" jawab </dev/tty
    echo >&2
    echo "$jawab"
}

gagal() {
    echo "GAGAL: $1" >&2
    exit 1
}

echo "=== Pemasangan otomatis Monitoring TA ==="
echo

RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
if [ "$RAM_MB" -lt 1700 ]; then
    echo "PERINGATAN: RAM server hanya ${RAM_MB} MB. Disarankan minimal 2 GB, kalau tidak build bisa gagal."
    echo
fi

GH_USER=$(tanya "Username GitHub kamu: ")
DOMAIN=$(tanya "Alamat server tanpa https:// (contoh monitoringta-andi.southeastasia.cloudapp.azure.com): ")
ADMIN_EMAIL=$(tanya "Email untuk akun admin: ")
ADMIN_PASSWORD=$(tanya_rahasia "Kata sandi admin (minimal 8, hanya huruf dan angka): ")
KODE_DOSEN=$(tanya "Kode pendaftaran dosen (kosongkan kalau dosen hanya ditambah admin): ")

[[ "$GH_USER" =~ ^[A-Za-z0-9-]+$ ]] || gagal "Username GitHub tidak valid."
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || gagal "Alamat server tidak valid (jangan pakai https:// atau garis miring)."
[[ "$ADMIN_EMAIL" =~ ^[^@[:space:]]+@[^@[:space:]]+$ ]] || gagal "Email admin tidak valid."
[[ "$ADMIN_PASSWORD" =~ ^[A-Za-z0-9]{8,}$ ]] || gagal "Kata sandi admin harus minimal 8 karakter, hanya huruf dan angka."
[[ -z "$KODE_DOSEN" || "$KODE_DOSEN" =~ ^[A-Za-z0-9]+$ ]] || gagal "Kode dosen hanya boleh huruf dan angka."

echo
echo "[1/5] Memasang Git dan Docker..."
sudo apt-get update -y
sudo apt-get install -y git curl openssl
if ! command -v docker > /dev/null 2>&1; then
    curl -fsSL https://get.docker.com | sudo sh
fi
sudo docker compose version > /dev/null 2>&1 || gagal "Docker Compose tidak ditemukan setelah pemasangan Docker."

echo "[2/5] Mengambil kode dari GitHub..."
cd "$HOME"
if [ -d monitoring-ta/.git ]; then
    git -C monitoring-ta pull --ff-only
else
    git clone "https://github.com/$GH_USER/monitoring-ta.git" || gagal "Tidak bisa mengambil repository. Pastikan repository 'monitoring-ta' bersifat Public dan username benar."
fi
cd monitoring-ta

echo "[3/5] Menulis pengaturan (.env)..."
if [ -f .env ]; then
    echo "File .env sudah ada, dipertahankan (kata sandi database lama tetap dipakai)."
else
    DB_PASSWORD=$(openssl rand -hex 16)
    DB_ROOT_PASSWORD=$(openssl rand -hex 16)
    cat > .env <<EOF
DOMAIN=$DOMAIN
DB_PASSWORD=$DB_PASSWORD
DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD
ADMIN_EMAIL=$ADMIN_EMAIL
ADMIN_PASSWORD=$ADMIN_PASSWORD
ADMIN_NAME=Koordinator Prodi
KODE_DAFTAR_DOSEN=$KODE_DOSEN
APP_NAME="Monitoring TA"
LOG_CHANNEL=stderr
EOF
    chmod 600 .env
fi

echo "[4/5] Membangun dan menjalankan aplikasi (pertama kali 5 sampai 10 menit)..."
sudo docker compose up -d --build

echo "[5/5] Menunggu aplikasi siap dan sertifikat HTTPS terbit..."
SIAP=0
for i in $(seq 1 40); do
    KODE=$(curl -ksS -o /dev/null -m 10 -w "%{http_code}" "https://$DOMAIN/login" || true)
    if [ "$KODE" = "200" ]; then
        SIAP=1
        break
    fi
    echo "  belum siap (percobaan $i/40)..."
    sleep 10
done

echo
if [ "$SIAP" = "1" ]; then
    echo "SELESAI. Aplikasi online di:"
    echo "  https://$DOMAIN"
    echo
    echo "Login admin:"
    echo "  email     : $ADMIN_EMAIL"
    echo "  kata sandi: yang tadi kamu ketik"
else
    echo "Aplikasi belum bisa dibuka dari luar. Kemungkinan penyebab:"
    echo "  - port 80/443 belum terbuka di jaringan server, atau alamat belum mengarah ke server ini"
    echo "  - build atau database masih berjalan (tunggu beberapa menit lalu coba buka alamatnya)"
    echo
    echo "Log terakhir (kirim ini ke Claude kalau butuh bantuan):"
    sudo docker compose logs --tail=40 app caddy
fi
