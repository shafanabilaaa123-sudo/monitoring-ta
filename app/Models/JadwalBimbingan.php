<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalBimbingan extends Model
{
    protected $table = 'jadwal_bimbingan';

    protected $fillable = [
        'tugas_akhir_id', 'waktu', 'topik', 'status', 'alasan_dosen', 'diingatkan',
    ];

    protected $casts = [
        'waktu' => 'datetime',
        'diingatkan' => 'boolean',
    ];

    public function tugasAkhir(): BelongsTo
    {
        return $this->belongsTo(TugasAkhir::class, 'tugas_akhir_id');
    }

    public function warnaBadge(): string
    {
        return match ($this->status) {
            'disetujui' => 'success',
            'ditolak' => 'danger',
            'selesai' => 'secondary',
            default => 'warning',
        };
    }

    public function label(): string
    {
        return ucfirst($this->status);
    }
}
