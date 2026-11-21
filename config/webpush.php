<?php

return [
    // Dibuat otomatis oleh: php artisan push:keys
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),

    // Harus berupa mailto: atau URL https
    'subject' => env('VAPID_SUBJECT', 'mailto:admin@kampus.test'),
];
