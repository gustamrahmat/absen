<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('sesi', ['pagi', 'sore']);
            $table->timestamp('waktu_absen')->nullable();
            $table->string('lokasi')->nullable();
            $table->decimal('jarak_meter', 8, 2)->nullable();
            $table->decimal('akurasi_gps', 8, 2)->nullable();
            $table->string('foto_url')->nullable(); // selfie, hanya sesi 'pagi'
            $table->string('device_id')->nullable();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha', 'cuti'])->default('hadir');
            // 'auto' = langsung disetujui sistem; 'menunggu' = butuh persetujuan HRD
            $table->enum('status_persetujuan', ['auto', 'menunggu', 'disetujui', 'ditolak'])->default('auto');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_approval')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal', 'sesi']); // cegah dobel presensi sesi yang sama di hari yang sama
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_presensi');
    }
};
