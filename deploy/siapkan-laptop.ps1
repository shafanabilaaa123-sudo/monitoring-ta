# Menyiapkan project Monitoring TA dan mengunggahnya ke GitHub.
#
# Cara pakai (PowerShell, dari folder monitoring-ta-kode\deploy):
#   powershell -ExecutionPolicy Bypass -File .\siapkan-laptop.ps1
#
# Syarat: Git, Composer, dan PHP sudah terpasang, dan folder "monitoring-ta"
# (project Laravel yang berisi file artisan) ada di sebelah folder "deploy".

$ErrorActionPreference = 'Continue'

$induk = Split-Path $PSScriptRoot -Parent
$proyek = Join-Path $induk 'monitoring-ta'

function Perlu($nama) {
    if (-not (Get-Command $nama -ErrorAction SilentlyContinue)) {
        Write-Host "Program '$nama' belum terpasang. Pasang dulu, lalu jalankan skrip ini lagi." -ForegroundColor Red
        exit 1
    }
}

Perlu git
Perlu composer
Perlu php

if (-not (Test-Path (Join-Path $proyek 'artisan'))) {
    Write-Host "Folder project tidak ditemukan: $proyek" -ForegroundColor Red
    Write-Host "Pastikan folder 'monitoring-ta' (yang berisi file artisan) ada di sebelah folder 'deploy'." -ForegroundColor Red
    exit 1
}

Write-Host ''
Write-Host '[1/4] Menyalin file terbaru ke project...' -ForegroundColor Cyan
robocopy $induk $proyek /E /XD monitoring-ta .git | Out-Null

Set-Location $proyek

Write-Host '[2/4] Memasang paket notifikasi push...' -ForegroundColor Cyan
composer require minishlink/web-push --no-interaction
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Pemasangan paket gagal. Kirim pesan error di atas ke Claude.' -ForegroundColor Red
    exit 1
}

Write-Host '[3/4] Menyiapkan Git...' -ForegroundColor Cyan
if (-not (Test-Path '.git')) { git init | Out-Null }

$emailGit = (git config user.email)
if (-not $emailGit) {
    $namaGit = Read-Host 'Nama kamu (untuk catatan Git)'
    $emailGit = Read-Host 'Email GitHub kamu'
    git config user.name $namaGit
    git config user.email $emailGit
}

git add .
git commit -m 'Monitoring TA versi cloud' | Out-Null
git branch -M main

Write-Host '[4/4] Mengunggah ke GitHub...' -ForegroundColor Cyan
$pengguna = Read-Host 'Username GitHub kamu'

Write-Host ''
Write-Host 'Sekarang buka https://github.com/new di browser, lalu:' -ForegroundColor Yellow
Write-Host '  - Repository name : monitoring-ta'
Write-Host '  - Pilih Public'
Write-Host '  - JANGAN centang README, .gitignore, atau license'
Write-Host '  - Klik Create repository'
Read-Host 'Kalau sudah selesai, tekan Enter di sini' | Out-Null

git remote remove origin 2>$null
git remote add origin "https://github.com/$pengguna/monitoring-ta.git"
git push -u origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host 'Unggah ke GitHub gagal. Pastikan repository sudah dibuat dan kamu login GitHub, lalu jalankan skrip ini lagi.' -ForegroundColor Red
    exit 1
}

Write-Host ''
Write-Host 'SELESAI. Kode sudah ada di GitHub.' -ForegroundColor Green
Write-Host ''
Write-Host 'Nanti, di dalam server, jalankan perintah ini (satu baris):' -ForegroundColor Yellow
Write-Host "bash <(curl -fsSL https://raw.githubusercontent.com/$pengguna/monitoring-ta/main/deploy/siapkan-server.sh)"
