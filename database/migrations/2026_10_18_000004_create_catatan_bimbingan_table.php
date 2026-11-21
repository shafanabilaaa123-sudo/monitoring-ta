<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_bimbingan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_akhir_id')->constrained('tugas_akhir')->cascadeOnDelete();
            $table->foreignId('jadwal_bimbingan_id')->nullable()->constrained('jadwal_bimbingan')->nullOnDelete();
            $table->text('isi');
            $table->text('revisi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_bimbingan');
    }
};
