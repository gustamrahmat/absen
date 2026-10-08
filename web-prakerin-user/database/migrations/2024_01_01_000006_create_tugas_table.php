<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_file_asli');           // nama file dari komputer peserta (untuk tampilan & unduhan)
            $table->string('path_file');                // lokasi di disk privat (nama acak, bukan nama asli)
            $table->string('tipe_mime', 100);
            $table->unsignedInteger('ukuran_kb');
            $table->timestamp('dilihat_admin_at')->nullable(); // diisi saat mentor/admin pertama kali membuka berkas
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
