<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Laporan harian digabung jadi 1 teks: kolom "kendala" dilebur ke "kegiatan",
// lalu "kegiatan" diganti nama jadi "laporan". Aman dijalankan walau tabel sudah berisi data.
return new class extends Migration
{
    public function up(): void
    {
        // 1) Lebur kendala ke dalam teks kegiatan untuk baris yang sudah ada
        DB::table('laporan_harian')
            ->whereNotNull('kendala')->where('kendala', '<>', '')
            ->orderBy('id')
            ->chunkById(200, function ($baris) {
                foreach ($baris as $b) {
                    DB::table('laporan_harian')->where('id', $b->id)
                        ->update(['kegiatan' => $b->kegiatan . "\n\nKendala: " . $b->kendala]);
                }
            });

        // 2) Buang kolom kendala, ganti nama kegiatan -> laporan (dua langkah terpisah supaya aman di semua versi Laravel)
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->dropColumn('kendala');
        });
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->renameColumn('kegiatan', 'laporan');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->renameColumn('laporan', 'kegiatan');
        });
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->text('kendala')->nullable()->after('kegiatan');
        });
    }
};
