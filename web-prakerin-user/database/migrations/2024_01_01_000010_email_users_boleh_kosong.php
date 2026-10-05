<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Peserta sekarang mendaftar dengan NIM/NISN saja (tanpa email), jadi kolom email harus boleh kosong.
// Indeks unik tetap ada: MySQL mengizinkan banyak baris dengan email NULL, tapi melarang email yang sama.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Sengaja kosong: mengembalikan ke NOT NULL akan gagal begitu ada peserta tanpa email.
    }
};
