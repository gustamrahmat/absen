<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kalender libur perusahaan. Menggantikan daftar 'libur' di config/prakerin.php.
// Hari raya (termasuk hari raya Islam) tanggalnya ditetapkan pemerintah tiap tahun (SKB 3 Menteri),
// jadi diisi manual dalam tanggal Masehi -- tidak dihitung dari kalender Hijriyah.
// Satu baris = satu hari libur. Kalau libur itu diganti hari kerja lain (mis. cuti bersama Senin
// diganti Sabtu), isi `tanggal_pengganti`: hari itu otomatis menjadi hari kerja WAJIB.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama');
            $table->enum('jenis', ['nasional', 'cuti_bersama', 'perusahaan'])->default('nasional');
            $table->date('tanggal_pengganti')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};
