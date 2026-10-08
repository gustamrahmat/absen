<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menyelaraskan skema Laravel dengan presensi.sql / data lama Node:
// - users.asal_sekolah & users.jurusan  (kolom UNIV/SEKOLAH dan PRODI di Data-Prakerin*.xlsx)
// - sesi_presensi.lat & lng             (koordinat HP saat scan, ada di presensi_slot pada presensi.sql)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('asal_sekolah', 150)->nullable()->after('foto_profil_url');
            $table->string('jurusan', 150)->nullable()->after('asal_sekolah');
        });

        Schema::table('sesi_presensi', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('lokasi');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_presensi', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['asal_sekolah', 'jurusan']);
        });
    }
};
