<?php

return [
    // Matikan halaman Daftar dengan PENDAFTARAN_AKTIF=false di .env
    'aktif' => filter_var(env('PENDAFTARAN_AKTIF', true), FILTER_VALIDATE_BOOLEAN),

    // Kode rahasia yang harus diketik dosen saat mendaftar.
    // Kosong = dosen tidak bisa mendaftar sendiri (hanya admin yang bisa menambah dosen).
    'kode_dosen' => (string) env('KODE_DAFTAR_DOSEN', ''),
];
