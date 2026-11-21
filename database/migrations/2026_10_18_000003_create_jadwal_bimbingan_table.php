<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_bimbingan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_akhir_id')->constrained('tugas_akhir')->cascadeOnDelete();
            $table->dateTime('waktu');
            $table->string('topik');
            $table->string('status', 20)->default('diajukan'); // diajukan, disetujui, ditolak, selesai
            $table->string('alasan_dosen', 500)->nullable();
            $table->boolean('diingatkan')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_bimbingan');
    }
};
