<?php

return [
    // Nama paket aplikasi Android (sama persis dengan yang dipakai PWABuilder)
    'package' => env('ANDROID_PACKAGE', 'com.monitoringta.app'),

    // Sidik jari SHA-256 sertifikat penanda tangan APK. Boleh lebih dari satu, pisahkan dengan koma.
    'sha256' => env('ANDROID_SHA256', ''),
];
