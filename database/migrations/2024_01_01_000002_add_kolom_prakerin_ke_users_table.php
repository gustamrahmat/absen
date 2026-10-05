<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CATATAN: Laravel sudah punya migration default untuk tabel "users" (kolom id, name, email,
// password, remember_token, timestamps -- dibuat otomatis saat `laravel new`). Migration ini
// MENAMBAH kolom khusus ekosistem Prakerin ke tabel yang sudah ada, bukan membuat tabel baru --
// jadi aman dijalankan baik project-nya masih baru maupun sudah pernah di-migrate sebelumnya.
//
// PENTING: field nama saya pakai kolom bawaan Laravel "name" (bukan "nama") dan kata sandi
// pakai kolom bawaan "password" (bukan "kata_sandi_hash") -- ini SESUAI konvensi default
// Laravel. Cek migration users bawaan project rekanmu dulu; kalau ternyata sudah di-custom
// jadi "nama"/"kata_sandi", sesuaikan nama kolom di file ini dan di Model/Controller nanti.

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_induk')->nullable()->unique()->after('email'); // NIM/NISN, diisi setelah diterima
            $table->enum('peran', ['peserta', 'mentor', 'admin', 'superadmin'])->default('peserta')->after('nomor_induk');
            $table->enum('status_akun', ['calon', 'diterima', 'aktif', 'selesai', 'ditolak'])->default('calon')->after('peran');
            $table->foreignId('departemen_id')->nullable()->after('status_akun')->constrained('departemen')->nullOnDelete();
            $table->uuid('uuid_kartu')->nullable()->unique()->after('departemen_id'); // isi QR kartu ID, diisi saat diterima
            $table->string('foto_profil_url')->nullable()->after('uuid_kartu'); // pasfoto, rute disiapkan dulu
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departemen_id');
            $table->dropColumn(['nomor_induk', 'peran', 'status_akun', 'uuid_kartu', 'foto_profil_url']);
        });
    }
};
