<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Melarang peramban menyimpan halaman (cache & bfcache). Tanpa ini, tombol back/forward Chrome
// menampilkan halaman presensi lama dari cache walaupun sesi sudah berakhir (logout), dan
// halaman login tampil lagi setelah login. Dipasang ke grup 'web' di bootstrap/app.php.
class TanpaCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $respons->headers->set('Pragma', 'no-cache');
        $respons->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $respons;
    }
}
