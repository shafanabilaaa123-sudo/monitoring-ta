#!/bin/bash
# Membuat server Ubuntu di Azure lewat Azure Cloud Shell (tanpa klik-klik di portal).
#
# Cara pakai:
#   1. Buka portal.azure.com, klik ikon ">_" (Cloud Shell) di bagian atas, pilih Bash.
#   2. Tempel dan jalankan:
#        bash <(curl -fsSL https://raw.githubusercontent.com/USERNAME/monitoring-ta/main/deploy/buat-server-azure.sh)
#
# Server dibuat di Singapura (southeastasia) dengan alamat gratis NAMA.southeastasia.cloudapp.azure.com.
set -euo pipefail

LOKASI="southeastasia"
GRUP="rg-monitoringta"
VM="vm-monitoringta"
UKURAN="Standard_B2s"   # 2 vCPU, 4 GB RAM

echo "=== Membuat server untuk Monitoring TA ==="
read -r -p "Nama alamat server (huruf kecil, angka, strip; contoh monitoringta-andi): " NAMA </dev/tty

if ! [[ "$NAMA" =~ ^[a-z][a-z0-9-]{2,40}$ ]]; then
    echo "Nama tidak valid. Pakai huruf kecil, angka, dan strip; mulai dengan huruf; minimal 3 karakter."
    exit 1
fi

echo
echo "Membuat server, tunggu 2 sampai 3 menit..."
az group create -n "$GRUP" -l "$LOKASI" -o none

az vm create \
    -g "$GRUP" -n "$VM" \
    --image Ubuntu2404 \
    --size "$UKURAN" \
    --admin-username azureuser \
    --generate-ssh-keys \
    --public-ip-address-dns-name "$NAMA" \
    --public-ip-sku Standard \
    -o none

echo "Membuka port web (80 dan 443)..."
az vm open-port -g "$GRUP" -n "$VM" --port 80 --priority 1010 -o none
az vm open-port -g "$GRUP" -n "$VM" --port 443 --priority 1020 -o none

FQDN="$NAMA.$LOKASI.cloudapp.azure.com"

echo
echo "SERVER SIAP."
echo "Alamat server : $FQDN   (catat ini)"
echo
echo "Langkah berikutnya, masuk ke server dari Cloud Shell yang sama:"
echo "  ssh azureuser@$FQDN"
echo
echo "Kalau muncul pertanyaan 'continue connecting', ketik: yes"
echo
echo "Kalau gagal dengan pesan ukuran/quota (SkuNotAvailable atau QuotaExceeded):"
echo "  jalankan lagi dengan ukuran lain, misalnya: UKURAN=Standard_B2als_v2 (ubah baris UKURAN di skrip)."
