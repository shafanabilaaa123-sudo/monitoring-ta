<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanBimbingan extends Model
{
    protected $table = 'catatan_bimbingan';

    protected $fillable = ['tugas_akhir_id', 'jadwal_bimbingan_id', 'isi', 'revisi'];

    public function tugasAkhir(): BelongsTo
    {
        return $this->belongsTo(TugasAkhir::class, 'tugas_akhir_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalBimbingan::class, 'jadwal_bimbingan_id');
    }
}
