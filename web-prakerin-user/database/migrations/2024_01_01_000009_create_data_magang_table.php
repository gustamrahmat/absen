<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Daftar induk peserta magang (hasil impor Excel). Peserta mendaftar akun sendiri (nama/email/sandi),
// lalu admin MENAUTKAN akun itu ke baris di sini lewat NIM/NISN (kolom user_id terisi).
// UUID kartu ID dibuat/disimpan di sini sejak impor, jadi kartu bisa dicetak sebelum peserta mendaftar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_magang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_induk', 50)->unique();      // NIM / NISN (teks, nol di depan aman)
            $table->string('nama', 150);
            $table->string('asal_sekolah', 150)->nullable();
            $table->string('jurusan', 150)->nullable();
            $table->foreignId('departemen_id')->nullable()->constrained('departemen')->nullOnDelete();
            $table->uuid('uuid')->unique();                    // isi QR kartu ID
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete(); // NULL = belum ditautkan
            $table->timestamp('ditautkan_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_magang');
    }
};
