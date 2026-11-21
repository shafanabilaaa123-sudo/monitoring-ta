<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TugasAkhir extends Model
{
    protected $table = 'tugas_akhir';

    public const TAHAPAN = [
        'Pengajuan Judul',
        'Proposal',
        'Bab 1-3',
        'Bab 4-5',
        'Siap Sidang',
        'Selesai',
    ];

    protected $fillable = [
        'mahasiswa_id', 'dosen_id', 'judul', 'deskripsi',
        'tahapan', 'status', 'aktivitas_terakhir',
    ];

    protected $casts = [
        'aktivitas_terakhir' => 'datetime',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalBimbingan::class, 'tugas_akhir_id');
    }

    public function catatan(): HasMany
    {
        return $this->hasMany(CatatanBimbingan::class, 'tugas_akhir_id');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class, 'tugas_akhir_id');
    }

    /** Persentase progres berdasarkan tahapan (0-100). */
    public function getPersenAttribute(): int
    {
        $indeks = array_search($this->tahapan, self::TAHAPAN, true);
        $indeks = $indeks === false ? 0 : $indeks;

        return (int) round($indeks / (count(self::TAHAPAN) - 1) * 100);
    }

    public function dapatDiaksesOleh(User $user): bool
    {
        return $user->role === 'admin'
            || $user->id === $this->mahasiswa_id
            || $user->id === $this->dosen_id;
    }

    /** Berjalan tetapi tidak ada aktivitas 14 hari terakhir. */
    public function tidakAktif(): bool
    {
        if ($this->status !== 'berjalan') {
            return false;
        }

        return $this->aktivitas_terakhir === null
            || $this->aktivitas_terakhir->lt(now()->subDays(14));
    }

    public function catatAktivitas(): void
    {
        $this->forceFill(['aktivitas_terakhir' => now()])->save();
    }
}
