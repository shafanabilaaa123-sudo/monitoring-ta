<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'nomor_induk'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** Tugas akhir milik mahasiswa ini. */
    public function tugasAkhir(): HasOne
    {
        return $this->hasOne(TugasAkhir::class, 'mahasiswa_id');
    }

    /** Daftar tugas akhir yang dibimbing dosen ini. */
    public function bimbingan(): HasMany
    {
        return $this->hasMany(TugasAkhir::class, 'dosen_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function labelPeran(): string
    {
        return match ($this->role) {
            'admin' => 'Admin / Koordinator',
            'dosen' => 'Dosen Pembimbing',
            default => 'Mahasiswa',
        };
    }
}
