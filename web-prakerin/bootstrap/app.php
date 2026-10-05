<?php

use App\Http\Middleware\TanpaCache;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman khusus peserta -> halaman login.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Pengguna yang SUDAH login lalu membuka /, /login, atau /daftar (mis. lewat tombol back) -> halaman presensi.
        // Tanpa ini Laravel memakai '/' sebagai tujuan, padahal '/' juga halaman khusus tamu, sehingga berputar (redirect loop).
        $middleware->redirectUsersTo(fn () => route('presensi.index'));

        // Semua respons web dilarang disimpan peramban (back/forward tidak bisa menampilkan halaman lama).
        $middleware->web(append: [TanpaCache::class]);

        // OPSIONAL: aktifkan HANYA kalau setelah lewat Cloudflare Tunnel tautan/redirect berubah jadi http://
        // (tanda: form tidak jalan / mixed content). Untuk produksi, ganti '*' dengan IP proxy yang sebenarnya.
        // $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
