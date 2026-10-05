<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $add = [
            'tipe_magang' => fn(Blueprint $t) => $t->enum('tipe_magang', ['smk','kuliah'])->default('smk'),
            'jenis_kelamin' => fn(Blueprint $t) => $t->enum('jenis_kelamin', ['L','P'])->nullable(),
            'no_ktp' => fn(Blueprint $t) => $t->string('no_ktp',16)->nullable(),
            'no_wa' => fn(Blueprint $t) => $t->string('no_wa',20)->nullable(),
            'alamat' => fn(Blueprint $t) => $t->text('alamat')->nullable(),
            'tempat_lahir' => fn(Blueprint $t) => $t->string('tempat_lahir',100)->nullable(),
            'tanggal_lahir' => fn(Blueprint $t) => $t->date('tanggal_lahir')->nullable(),
            'nis_nim' => fn(Blueprint $t) => $t->string('nis_nim',50)->nullable(),
            'divisi' => fn(Blueprint $t) => $t->string('divisi',100)->nullable(),
            'foto_pas' => fn(Blueprint $t) => $t->string('foto_pas')->nullable(),
            'pembimbing' => fn(Blueprint $t) => $t->string('pembimbing',150)->nullable(),
            'no_wa_pembimbing' => fn(Blueprint $t) => $t->string('no_wa_pembimbing',20)->nullable(),
            'cv_path' => fn(Blueprint $t) => $t->string('cv_path')->nullable(),
            'surat_permohonan_path' => fn(Blueprint $t) => $t->string('surat_permohonan_path')->nullable(),
            'periode_magang' => fn(Blueprint $t) => $t->string('periode_magang',100)->nullable(),
            'status_kehadiran_awal' => fn(Blueprint $t) => $t->string('status_kehadiran_awal',10)->default('hadir'),
            'tempat_prakerin' => fn(Blueprint $t) => $t->string('tempat_prakerin',100)->nullable(),
            'tgl_mulai' => fn(Blueprint $t) => $t->date('tgl_mulai')->nullable(),
            'tgl_selesai' => fn(Blueprint $t) => $t->date('tgl_selesai')->nullable(),
            'keterangan' => fn(Blueprint $t) => $t->string('keterangan')->nullable(),
            'status_magang' => fn(Blueprint $t) => $t->string('status_magang',20)->default('ACTIVE'),
        ];

        $missing = array_filter($add, fn($fn, $name) => !Schema::hasColumn('data_magang', $name), ARRAY_FILTER_USE_BOTH);
        if ($missing) {
            Schema::table('data_magang', function (Blueprint $table) use ($missing) {
                foreach ($missing as $fn) $fn($table);
            });
        }
    }

    public function down(): void
    {
        $cols = [
            'tipe_magang','jenis_kelamin','no_ktp','no_wa','alamat','tempat_lahir',
            'tanggal_lahir','nis_nim','divisi','foto_pas','pembimbing','no_wa_pembimbing',
            'cv_path','surat_permohonan_path','periode_magang','status_kehadiran_awal',
            'tempat_prakerin','tgl_mulai','tgl_selesai','keterangan','status_magang'
        ];
        $drop = array_values(array_filter($cols, fn($c)=>Schema::hasColumn('data_magang',$c)));
        if ($drop) Schema::table('data_magang', fn(Blueprint $t)=>$t->dropColumn($drop));
    }
};
