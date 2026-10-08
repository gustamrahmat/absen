<?php

namespace Database\Seeders;

use App\Models\HariLibur;
use Illuminate\Database\Seeder;

// Jalankan: php artisan db:seed --class=HariLiburSeeder   (aman diulang, memakai updateOrCreate)
// Tanggal di bawah dipindahkan dari daftar 'libur' lama di config/prakerin.php.
// Tambahkan sisanya dari SKB 3 Menteri / keputusan HR. Hari raya Islam diisi dalam tanggal Masehi
// sesuai SKB (bukan dihitung dari Hijriyah). Contoh cuti bersama yang diganti Sabtu:
//   ['tanggal' => '2027-01-04', 'nama' => 'Cuti Bersama ...', 'jenis' => 'cuti_bersama', 'tanggal_pengganti' => '2027-01-09'],
// Bisa juga lewat Artisan: php artisan prakerin:libur 2026-12-25 "Hari Raya Natal"
class HariLiburSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['tanggal' => '2026-08-17', 'nama' => 'Hari Kemerdekaan RI', 'jenis' => 'nasional', 'tanggal_pengganti' => null],
            ['tanggal' => '2026-12-25', 'nama' => 'Hari Raya Natal', 'jenis' => 'nasional', 'tanggal_pengganti' => null],
            // Cuti bersama Natal 24 Des: bagi swasta opsional -- tambahkan kalau HR memutuskan libur.
        ];

        foreach ($daftar as $h) {
            HariLibur::updateOrCreate(['tanggal' => $h['tanggal']], $h);
        }
    }
}
