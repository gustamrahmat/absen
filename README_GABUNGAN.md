# Project Prakerin Gabungan

Project ini menggabungkan:

- Fondasi `absen-main`: login/register, presensi GPS + QR, sesi pagi/sore, laporan harian, tugas, kartu ID.
- Fitur admin dari `presensiprakerin`: dashboard presensi, tambah peserta, QR, import Excel, export rekap, ubah status, ACC laporan, hapus peserta.
- Database acuan: arsitektur `users -> data_magang -> sesi_presensi -> laporan_harian`.

## Struktur utama

```text
users
 ├── data_magang
 ├── sesi_presensi
 │      └── laporan_harian
 ├── tugas
 └── log_perubahan_status

departemen
```

Tabel lama `peserta`, `admins`, `presensi_harian`, dan `presensi_slot` tidak dipakai lagi oleh controller gabungan.

## Jika memakai database lama

Gunakan:

`database/sql/migrasi_presensi_ke_struktur_teman.sql`

Backup database terlebih dahulu. SQL tersebut mempertahankan tabel lama sebagai data legacy.

## Jika membuat database baru

```bash
composer install
php artisan migrate
php artisan storage:link
php artisan key:generate
```

Kemudian isi `.env` sesuai database MySQL.

## Dependency

Project memakai:

- Laravel 13
- PHP 8.3+
- PhpSpreadsheet
- Endroid QR Code

Setelah mengubah `composer.json`:

```bash
composer update
```

## Admin

Akun dengan:

```text
peran = admin
```

atau

```text
peran = superadmin
```

dapat membuka:

```text
/admin/presensi
```

Login admin otomatis diarahkan ke dashboard admin.

## Catatan

Import Excel memasukkan daftar induk ke `data_magang`. Akun peserta dapat ditautkan berdasarkan NIM/NISN sesuai service `PenautanPeserta`.

Peserta yang dibuat langsung dari menu Admin otomatis memiliki:

- akun `users`
- `uuid_kartu`
- data `data_magang`
- QR kartu ID
- password awal = nomor induk
