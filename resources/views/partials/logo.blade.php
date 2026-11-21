{{--
    Logo kampus. Taruh berkas logo di public/logo-kampus.png (PNG persegi, minimal 256 piksel).
    Kalau berkasnya belum ada, yang tampil adalah ikon topi toga biasa.
    Pemakaian: @include('partials.logo', ['ukuran' => 48])
--}}
@php
    $ukuranLogo = $ukuran ?? 48;
    $adaLogo = file_exists(public_path('logo-kampus.png'));
@endphp
<span class="logo-bulat" style="width: {{ $ukuranLogo }}px; height: {{ $ukuranLogo }}px;">
    @if($adaLogo)
        <img src="{{ asset('logo-kampus.png') }}?v={{ filemtime(public_path('logo-kampus.png')) }}" alt="Logo kampus">
    @else
        <svg viewBox="0 0 24 24" fill="none" stroke="#14507f" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-label="Logo">
            <path d="M2 9l10-5 10 5-10 5z"/>
            <path d="M6 11.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"/>
            <path d="M22 9v6"/>
        </svg>
    @endif
</span>
