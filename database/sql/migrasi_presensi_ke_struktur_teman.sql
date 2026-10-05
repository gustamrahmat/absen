-- ============================================================
-- MIGRASI DATABASE `presensi` MILIK KAMU
-- Menyesuaikan arsitektur `presensi (1).sql` milik teman
-- Aman: tabel lama TIDAK dihapus.
--
-- Jalankan di database `presensi` setelah BACKUP database terlebih dahulu.
-- MySQL 8 / MariaDB 10.4+
-- ============================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. TABEL DEPARTEMEN
-- ============================================================
CREATE TABLE IF NOT EXISTS `departemen` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `kuota` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departemen_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ambil departemen dari data `peserta` lama.
INSERT INTO `departemen` (`nama`, `created_at`, `updated_at`)
SELECT DISTINCT TRIM(`departemen`), NOW(), NOW()
FROM `peserta`
WHERE `departemen` IS NOT NULL
  AND TRIM(`departemen`) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM `departemen` d
      WHERE LOWER(TRIM(d.`nama`)) = LOWER(TRIM(`peserta`.`departemen`))
  );

-- ============================================================
-- 2. TABEL USERS
-- Arsitektur baru: peserta/admin berada di satu tabel users.
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nomor_induk` varchar(255) DEFAULT NULL,
  `peran` enum('peserta','mentor','admin','superadmin') NOT NULL DEFAULT 'peserta',
  `status_akun` enum('calon','diterima','aktif','selesai','ditolak') NOT NULL DEFAULT 'calon',
  `departemen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uuid_kartu` char(36) DEFAULT NULL,
  `foto_profil_url` varchar(255) DEFAULT NULL,
  `asal_sekolah` varchar(150) DEFAULT NULL,
  `jurusan` varchar(150) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nomor_induk_unique` (`nomor_induk`),
  UNIQUE KEY `users_uuid_kartu_unique` (`uuid_kartu`),
  KEY `users_departemen_id_foreign` (`departemen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. PINDAHKAN ADMIN LAMA -> USERS
-- ============================================================
INSERT INTO `users`
(`name`, `email`, `nomor_induk`, `peran`, `status_akun`,
 `departemen_id`, `uuid_kartu`, `password`, `created_at`, `updated_at`)
SELECT
  a.`nama`,
  a.`email`,
  NULL,
  'admin',
  'aktif',
  NULL,
  NULL,
  a.`password`,
  a.`created_at`,
  a.`updated_at`
FROM `admins` a
WHERE NOT EXISTS (
    SELECT 1 FROM `users` u
    WHERE u.`email` = a.`email`
);

-- ============================================================
-- 4. TABEL DATA_MAGANG
-- Dibuat lebih lengkap supaya biodata dari tabel peserta lama
-- tidak hilang.
-- ============================================================
CREATE TABLE IF NOT EXISTS `data_magang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomor_induk` varchar(50) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `tipe_magang` enum('smk','kuliah') NOT NULL DEFAULT 'smk',
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `no_ktp` varchar(16) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nis_nim` varchar(50) DEFAULT NULL,
  `asal_sekolah` varchar(150) DEFAULT NULL,
  `jurusan` varchar(150) DEFAULT NULL,
  `departemen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `divisi` varchar(100) DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ditautkan_pada` timestamp NULL DEFAULT NULL,
  `foto_pas` varchar(255) DEFAULT NULL,
  `pembimbing` varchar(150) DEFAULT NULL,
  `no_wa_pembimbing` varchar(20) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `surat_permohonan_path` varchar(255) DEFAULT NULL,
  `periode_magang` varchar(100) DEFAULT NULL,
  `status_kehadiran_awal` varchar(10) NOT NULL DEFAULT 'hadir',
  `tempat_prakerin` varchar(100) DEFAULT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `status_magang` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_magang_nomor_induk_unique` (`nomor_induk`),
  UNIQUE KEY `data_magang_uuid_unique` (`uuid`),
  UNIQUE KEY `data_magang_user_id_unique` (`user_id`),
  KEY `data_magang_departemen_id_foreign` (`departemen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PINDAHKAN PESERTA LAMA -> DATA_MAGANG
-- ============================================================
INSERT INTO `data_magang`
(`nomor_induk`, `nama`, `tipe_magang`, `jenis_kelamin`, `no_ktp`,
 `no_wa`, `alamat`, `tempat_lahir`, `tanggal_lahir`, `nis_nim`,
 `asal_sekolah`, `jurusan`, `departemen_id`, `divisi`, `uuid`,
 `foto_pas`, `pembimbing`, `no_wa_pembimbing`, `cv_path`,
 `surat_permohonan_path`, `periode_magang`, `status_kehadiran_awal`,
 `tempat_prakerin`, `tgl_mulai`, `tgl_selesai`, `keterangan`,
 `status_magang`, `created_at`, `updated_at`)
SELECT
  p.`nomor_induk`,
  p.`nama`,
  p.`tipe_magang`,
  p.`jenis_kelamin`,
  p.`no_ktp`,
  p.`no_wa`,
  p.`alamat`,
  p.`tempat_lahir`,
  p.`tanggal_lahir`,
  p.`nis_nim`,
  p.`asal_sekolah`,
  p.`jurusan`,
  d.`id`,
  p.`divisi`,
  p.`uuid`,
  p.`foto_pas`,
  p.`pembimbing`,
  p.`no_wa_pembimbing`,
  p.`cv_path`,
  p.`surat_permohonan_path`,
  p.`periode_magang`,
  p.`status_kehadiran_awal`,
  p.`tempat_prakerin`,
  p.`tgl_mulai`,
  p.`tgl_selesai`,
  p.`keterangan`,
  p.`status_magang`,
  p.`created_at`,
  p.`updated_at`
FROM `peserta` p
LEFT JOIN `departemen` d
  ON LOWER(TRIM(d.`nama`)) = LOWER(TRIM(p.`departemen`))
WHERE NOT EXISTS (
    SELECT 1
    FROM `data_magang` dm
    WHERE dm.`nomor_induk` = p.`nomor_induk`
);

-- ============================================================
-- 6. BUAT AKUN USERS UNTUK PESERTA LAMA
--
-- Karena tabel users temanmu mewajibkan email, peserta lama
-- yang tidak punya email diberi email teknis:
-- nomorinduk@legacy.local
--
-- Email ini bisa diganti nanti dari aplikasi.
-- Password tetap memakai hash password lama.
-- ============================================================
INSERT INTO `users`
(`name`, `email`, `nomor_induk`, `peran`, `status_akun`,
 `departemen_id`, `uuid_kartu`, `asal_sekolah`, `jurusan`,
 `password`, `created_at`, `updated_at`)
SELECT
  p.`nama`,
  CONCAT(
    'peserta.',
    REPLACE(REPLACE(REPLACE(p.`nomor_induk`, ' ', ''), '/', ''), '.', ''),
    '@legacy.local'
  ),
  p.`nomor_induk`,
  'peserta',
  CASE
    WHEN UPPER(p.`status_magang`) = 'ACTIVE' THEN 'aktif'
    WHEN UPPER(p.`status_magang`) = 'SELESAI' THEN 'selesai'
    WHEN UPPER(p.`status_magang`) = 'DITOLAK' THEN 'ditolak'
    ELSE 'diterima'
  END,
  d.`id`,
  p.`uuid`,
  p.`asal_sekolah`,
  p.`jurusan`,
  COALESCE(p.`password`, ''),
  p.`created_at`,
  p.`updated_at`
FROM `peserta` p
LEFT JOIN `departemen` d
  ON LOWER(TRIM(d.`nama`)) = LOWER(TRIM(p.`departemen`))
WHERE NOT EXISTS (
    SELECT 1 FROM `users` u
    WHERE u.`nomor_induk` = p.`nomor_induk`
);

-- ============================================================
-- 7. HUBUNGKAN DATA_MAGANG -> USERS
-- ============================================================
UPDATE `data_magang` dm
JOIN `users` u
  ON u.`nomor_induk` = dm.`nomor_induk`
SET
  dm.`user_id` = u.`id`,
  dm.`ditautkan_pada` = COALESCE(dm.`ditautkan_pada`, NOW());

-- ============================================================
-- 8. TABEL SESI_PRESENSI (ARSITEKTUR BARU)
-- Satu baris = satu peserta + satu tanggal + satu sesi.
-- ============================================================
CREATE TABLE IF NOT EXISTS `sesi_presensi` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `sesi` enum('pagi','sore') NOT NULL,
  `waktu_absen` timestamp NULL DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `jarak_meter` decimal(8,2) DEFAULT NULL,
  `akurasi_gps` decimal(8,2) DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `status` enum('hadir','izin','sakit','alpha','cuti') NOT NULL DEFAULT 'hadir',
  `status_persetujuan` enum('auto','menunggu','disetujui','ditolak') NOT NULL DEFAULT 'auto',
  `disetujui_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `catatan_approval` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sesi_presensi_user_id_tanggal_sesi_unique`
    (`user_id`,`tanggal`,`sesi`),
  KEY `sesi_presensi_disetujui_oleh_foreign` (`disetujui_oleh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. MIGRASI PRESENSI SLOT LAMA -> SESI_PRESENSI
--
-- peserta_id lama
--       ↓
-- peserta.nomor_induk
--       ↓
-- users.nomor_induk
--       ↓
-- sesi_presensi.user_id
--
-- slot pagi -> sesi pagi
-- slot sore -> sesi sore
-- ============================================================
INSERT INTO `sesi_presensi`
(`user_id`, `tanggal`, `sesi`, `waktu_absen`, `lokasi`,
 `lat`, `lng`, `jarak_meter`, `foto_url`, `status`,
 `status_persetujuan`, `disetujui_oleh`, `catatan_approval`,
 `created_at`, `updated_at`)
SELECT
  u.`id`,
  ph.`tanggal`,
  CASE
    WHEN LOWER(ps.`slot`) IN ('pagi','morning') THEN 'pagi'
    ELSE 'sore'
  END,
  TIMESTAMP(ph.`tanggal`, ps.`jam_scan`),
  ps.`lokasi`,
  ps.`lat`,
  ps.`lng`,
  CASE
    WHEN ps.`jarak` REGEXP '^[0-9]+([.][0-9]+)?$'
    THEN CAST(ps.`jarak` AS DECIMAL(8,2))
    ELSE NULL
  END,
  ps.`foto_selfie`,
  CASE
    WHEN LOWER(ph.`status`) IN ('izin','sakit','alpha','cuti')
      THEN LOWER(ph.`status`)
    ELSE 'hadir'
  END,
  CASE
    WHEN LOWER(ph.`status`) = 'menunggu_acc' THEN 'menunggu'
    WHEN LOWER(ph.`status`) IN ('ditolak') THEN 'ditolak'
    WHEN LOWER(ph.`status`) IN ('hadir','izin','sakit','alpha','cuti')
      THEN 'disetujui'
    ELSE 'auto'
  END,
  au.`id`,
  ph.`catatan_admin`,
  ps.`created_at`,
  ps.`updated_at`
FROM `presensi_slot` ps
JOIN `presensi_harian` ph
  ON ph.`id` = ps.`presensi_harian_id`
JOIN `peserta` p
  ON p.`id` = ph.`peserta_id`
JOIN `users` u
  ON u.`nomor_induk` = p.`nomor_induk`
LEFT JOIN `admins` a
  ON a.`id` = ph.`acc_by`
LEFT JOIN `users` au
  ON au.`email` = a.`email`
WHERE NOT EXISTS (
    SELECT 1
    FROM `sesi_presensi` sp
    WHERE sp.`user_id` = u.`id`
      AND sp.`tanggal` = ph.`tanggal`
      AND sp.`sesi` =
        CASE
          WHEN LOWER(ps.`slot`) IN ('pagi','morning') THEN 'pagi'
          ELSE 'sore'
        END
);

-- ============================================================
-- 10. TABEL LAPORAN_HARIAN
-- Di arsitektur teman: laporan terkait langsung ke sesi presensi.
-- ============================================================
CREATE TABLE IF NOT EXISTS `laporan_harian` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesi_presensi_id` bigint(20) UNSIGNED NOT NULL,
  `laporan` text NOT NULL,
  `status_review` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'disetujui',
  `direview_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `direview_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `laporan_harian_sesi_presensi_id_unique` (`sesi_presensi_id`),
  KEY `laporan_harian_direview_oleh_foreign` (`direview_oleh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. PINDAHKAN LAPORAN KEGIATAN LAMA -> LAPORAN_HARIAN
-- ============================================================
INSERT INTO `laporan_harian`
(`sesi_presensi_id`, `laporan`, `status_review`,
 `direview_oleh`, `direview_pada`, `created_at`, `updated_at`)
SELECT
  sp.`id`,
  ps.`laporan_kegiatan`,
  CASE
    WHEN sp.`status_persetujuan` = 'menunggu' THEN 'menunggu'
    WHEN sp.`status_persetujuan` = 'ditolak' THEN 'ditolak'
    ELSE 'disetujui'
  END,
  sp.`disetujui_oleh`,
  NULL,
  ps.`created_at`,
  ps.`updated_at`
FROM `presensi_slot` ps
JOIN `presensi_harian` ph
  ON ph.`id` = ps.`presensi_harian_id`
JOIN `peserta` p
  ON p.`id` = ph.`peserta_id`
JOIN `users` u
  ON u.`nomor_induk` = p.`nomor_induk`
JOIN `sesi_presensi` sp
  ON sp.`user_id` = u.`id`
 AND sp.`tanggal` = ph.`tanggal`
 AND sp.`sesi` =
    CASE
      WHEN LOWER(ps.`slot`) IN ('pagi','morning') THEN 'pagi'
      ELSE 'sore'
    END
WHERE ps.`laporan_kegiatan` IS NOT NULL
  AND TRIM(ps.`laporan_kegiatan`) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM `laporan_harian` lh
      WHERE lh.`sesi_presensi_id` = sp.`id`
  );

-- ============================================================
-- 12. TABEL LOG PERUBAHAN STATUS
-- ============================================================
CREATE TABLE IF NOT EXISTS `log_perubahan_status` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `status_baru` enum('hadir','izin','sakit','alpha','cuti') NOT NULL,
  `catatan` text DEFAULT NULL,
  `diubah_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_perubahan_status_user_id_foreign` (`user_id`),
  KEY `log_perubahan_status_diubah_oleh_foreign` (`diubah_oleh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. TABEL TUGAS MILIK TEMAN
-- Dibuat agar struktur utama project kamu tersedia.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tugas` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nama_file_asli` varchar(255) NOT NULL,
  `path_file` varchar(255) NOT NULL,
  `tipe_mime` varchar(100) NOT NULL,
  `ukuran_kb` int(10) UNSIGNED NOT NULL,
  `dilihat_admin_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tugas_user_id_created_at_index` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. TAMBAHKAN FOREIGN KEY
-- Cek dulu supaya script bisa dijalankan pada database yang
-- belum memiliki FK baru.
-- ============================================================

-- FK data_magang -> departemen
ALTER TABLE `data_magang`
  ADD CONSTRAINT `data_magang_departemen_id_foreign`
  FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`)
  ON DELETE SET NULL;

ALTER TABLE `data_magang`
  ADD CONSTRAINT `data_magang_user_id_foreign`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE SET NULL;

-- FK users -> departemen
ALTER TABLE `users`
  ADD CONSTRAINT `users_departemen_id_foreign`
  FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`)
  ON DELETE SET NULL;

-- FK sesi_presensi -> users
ALTER TABLE `sesi_presensi`
  ADD CONSTRAINT `sesi_presensi_user_id_foreign`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `sesi_presensi`
  ADD CONSTRAINT `sesi_presensi_disetujui_oleh_foreign`
  FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`)
  ON DELETE SET NULL;

-- FK laporan_harian -> sesi_presensi/users
ALTER TABLE `laporan_harian`
  ADD CONSTRAINT `laporan_harian_sesi_presensi_id_foreign`
  FOREIGN KEY (`sesi_presensi_id`) REFERENCES `sesi_presensi` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `laporan_harian`
  ADD CONSTRAINT `laporan_harian_direview_oleh_foreign`
  FOREIGN KEY (`direview_oleh`) REFERENCES `users` (`id`)
  ON DELETE SET NULL;

-- FK log status
ALTER TABLE `log_perubahan_status`
  ADD CONSTRAINT `log_perubahan_status_user_id_foreign`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `log_perubahan_status`
  ADD CONSTRAINT `log_perubahan_status_diubah_oleh_foreign`
  FOREIGN KEY (`diubah_oleh`) REFERENCES `users` (`id`)
  ON DELETE SET NULL;

-- FK tugas
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_user_id_foreign`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- ============================================================
-- HASIL AKHIR
--
-- Struktur baru:
--
-- users
--   ├── data_magang
--   └── sesi_presensi
--          └── laporan_harian
--
-- tabel lama tetap ada:
--   admins
--   peserta
--   presensi_harian
--   presensi_slot
--
-- Jadi data lama tidak langsung hilang.
-- Setelah controller Laravel sudah memakai struktur baru,
-- tabel lama bisa dihapus pada tahap berikutnya.
-- ============================================================
