<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function unduh(Request $request, Dokumen $dokumen)
    {
        abort_unless($dokumen->tugasAkhir->dapatDiaksesOleh($request->user()), 403);
        abort_unless(Storage::disk('public')->exists($dokumen->path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('public')->download($dokumen->path, $dokumen->nama_asli);
    }
}
