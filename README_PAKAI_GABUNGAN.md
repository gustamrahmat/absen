# Presensi Prakerin Gabungan — versi perbaikan

Versi ini menyesuaikan database yang sudah kamu punya:

- **Admin login memakai tabel `admins`**.
- **Peserta/mentor login memakai tabel `users`**.
- Admin tidak perlu dipindahkan ke `users`.
- Session default memakai file, jadi database lama tidak wajib punya tabel `sessions`.
- Dependency sudah disesuaikan untuk **PHP 8.3** (`endroid/qr-code ^5`, Pest 4).

## Instalasi di Windows / Laragon

```powershell
cd "D:\Maganghub\projectprakerin\gabungan 2"
composer install
```

Buat `.env`:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Atur `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=presensi
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
```

### Database yang sudah ada

**Jangan jalankan `php artisan migrate:fresh`.**

Database `presensi` yang sudah berisi tabel dan data boleh langsung dipakai. Jika semua tabel yang dibutuhkan sudah ada, tidak perlu migrate.

Kalau ingin mengecek:

```powershell
php artisan migrate:status
```

### Buat akun admin pertama

```powershell
php artisan db:seed --class=AdminSeeder
```

Akun default:

- Email: `admin@presensi.test`
- Password: `admin12345`

Password tetap disimpan dalam bentuk bcrypt/hash.

### Storage dan jalankan

```powershell
php artisan storage:link
php artisan config:clear
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Admin dapat login dari halaman login yang sama. Sistem akan mengecek tabel `admins` terlebih dahulu. Peserta tetap menggunakan tabel `users`.

> Untuk keamanan saat sudah dipakai sungguhan, segera ganti password admin default.
