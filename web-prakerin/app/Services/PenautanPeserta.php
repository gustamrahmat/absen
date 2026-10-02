<?php

namespace App\Services;

use App\Models\DataMagang;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Menautkan AKUN peserta (yang daftar sendiri lewat web) ke baris data magang dari Excel, lewat NIM/NISN.
// Setelah ditautkan: akun mendapat nomor induk, divisi, asal sekolah, jurusan, dan UUID kartu (uuid_kartu),
// sehingga peserta bisa scan presensi. Dipanggil dari Artisan (prakerin:tautkan) maupun dari halaman admin:
//     try { app(PenautanPeserta::class)->jalankan($user, $nomorInduk); }
//     catch (\DomainException $e) { /* $e->getMessage() = pesan Indonesia yang siap ditampilkan */ }
class PenautanPeserta
{
    public function jalankan(User $user, string $nomorInduk, bool $timpaNama = false): DataMagang
    {
        $nomorInduk = trim($nomorInduk);

        return DB::transaction(function () use ($user, $nomorInduk, $timpaNama) {
            $akun = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $data = DataMagang::where('nomor_induk', $nomorInduk)->lockForUpdate()->first();

            if ($akun->peran !== 'peserta') {
                throw new \DomainException('Hanya akun berperan peserta yang bisa ditautkan.');
            }
            if (! $data) {
                throw new \DomainException("NIM/NISN {$nomorInduk} tidak ditemukan di data magang. Impor file Excel-nya dulu.");
            }
            if ($data->user_id !== null && (int) $data->user_id !== (int) $akun->id) {
                throw new \DomainException("NIM/NISN {$nomorInduk} sudah ditautkan ke akun lain.");
            }
            if ($akun->nomor_induk !== null && $akun->nomor_induk !== $nomorInduk) {
                throw new \DomainException("Akun ini sudah ditautkan ke NIM/NISN {$akun->nomor_induk}.");
            }
            $dipakaiAkunLain = User::where('id', '<>', $akun->id)
                ->where(fn ($q) => $q->where('nomor_induk', $nomorInduk)->orWhere('uuid_kartu', $data->uuid))
                ->exists();
            if ($dipakaiAkunLain) {
                throw new \DomainException('Nomor induk atau UUID kartu ini sudah dipakai akun lain.');
            }

            $akun->fill([
                'nomor_induk' => $data->nomor_induk,
                'uuid_kartu' => $data->uuid,
                'departemen_id' => $data->departemen_id,
                'asal_sekolah' => $data->asal_sekolah,
                'jurusan' => $data->jurusan,
                'status_akun' => config('prakerin.status_setelah_tautan'),
            ]);
            if ($timpaNama) {
                $akun->name = $data->nama; // nama resmi dari Excel menggantikan nama yang diketik saat daftar
            }
            $akun->save();

            $data->update(['user_id' => $akun->id, 'ditautkan_pada' => now()]);

            return $data;
        });
    }
}
