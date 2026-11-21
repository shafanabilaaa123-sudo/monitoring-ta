<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dokumen extends Model
{
    protected $table = 'dokumen';

    protected $fillable = ['tugas_akhir_id', 'nama_asli', 'path', 'ukuran', 'keterangan'];

    public function tugasAkhir(): BelongsTo
    {
        return $this->belongsTo(TugasAkhir::class, 'tugas_akhir_id');
    }

    public function ukuranTerbaca(): string
    {
        if ($this->ukuran >= 1048576) {
            return number_format($this->ukuran / 1048576, 1) . ' MB';
        }

        return number_format(max($this->ukuran, 1) / 1024, 0) . ' KB';
    }
}
