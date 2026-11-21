<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tampilan pagination memakai Bootstrap 5
        Paginator::useBootstrapFive();

        // Zona waktu WIB dan bahasa Indonesia untuk tanggal
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');

        // Di belakang tunnel / proxy HTTPS, pastikan semua tautan dan form memakai https
        // (kalau tidak, browser memblokir form sebagai "mixed content").
        $lewatHttps = request()->header('X-Forwarded-Proto') === 'https'
            || str_starts_with((string) config('app.url'), 'https://');

        if ($lewatHttps) {
            URL::forceScheme('https');
        }
    }
}
