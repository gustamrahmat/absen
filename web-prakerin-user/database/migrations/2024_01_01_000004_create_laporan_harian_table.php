<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_presensi_id')->unique()->constrained('sesi_presensi')->cascadeOnDelete();
            $table->text('kegiatan');
            $table->text('kendala')->nullable();
            $table->enum('status_review', ['menunggu', 'disetujui', 'ditolak'])->default('disetujui'); // default auto-ACC
            $table->foreignId('direview_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('direview_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_harian');
    }
};
