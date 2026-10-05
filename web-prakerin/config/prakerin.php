<?php

// Pengaturan ekosistem presensi prakerin. Semua angka aturan ditaruh di sini
// supaya mudah diubah (dan mudah dipindah ke aplikasi utama) tanpa menyentuh controller.

return [

    // Jendela waktu sesi (jam, zona waktu mengikuti APP_TIMEZONE = Asia/Jakarta). Update HRD terbaru.
    'jendela' => [
        'pagi' => ['mulai' => 6, 'selesai' => 13],
        'sore' => ['mulai' => 13, 'selesai' => 18],
    ],

    // Hari kerja WAJIB memakai nomor hari ISO: 1 = Senin ... 7 = Minggu. Sekarang Senin-Jumat.
    // Di luar hari ini presensi tetap boleh, tidak wajib, dan menunggu persetujuan admin.
    // Hari libur (nasional, cuti bersama, perusahaan) dan hari kerja pengganti ada di TABEL `hari_libur`
    // (kelola dengan `php artisan prakerin:libur`), bukan di sini. Lihat App\Services\KalenderKerja.
    'hari_kerja' => [1, 2, 3, 4, 5],

    // Batas akurasi GPS (meter). Dicek di peramban DAN di server.
    // CATATAN: 7 m itu ketat -- banyak HP hanya mencapai 10-20 m di dalam bangunan. Longgarkan kalau banyak peserta gagal scan.
    'maks_akurasi_meter' => 7,

    // Batas ukuran foto selfie (KB) setelah di-decode.
    'maks_foto_kb' => 2048,

    // Titik pos absen yang sah -- GANTI koordinat & radius sesuai lokasi asli perusahaan.
    'pos' => [
        ['nama' => 'Pos 1', 'lat' => -7.5062152, 'lng' => 110.2247733, 'radius' => 35],
        ['nama' => 'Pos 2', 'lat' => -7.5043105, 'lng' => 110.2265946, 'radius' => 79],
        ['nama' => 'Pos 3', 'lat' => -7.5021834, 'lng' => 110.2286008, 'radius' => 43],
        ['nama' => 'Pos 4', 'lat' => -7.5021392, 'lng' => 110.2271552, 'radius' => 24],
        ['nama' => 'Pos 5', 'lat' => -7.5051948, 'lng' => 110.2254398, 'radius' => 10],
    ],

    // Peran yang boleh melihat foto selfie & berkas tugas milik peserta lain.
    'peran_peninjau' => ['mentor', 'admin', 'superadmin'],

    // Peran yang boleh membuka halaman cetak kartu ID.
    'peran_admin' => ['admin', 'superadmin'],

    // status_akun peserta setelah admin menautkannya ke data Excel (lewat NIM/NISN).
    'status_setelah_tautan' => 'aktif',
];
