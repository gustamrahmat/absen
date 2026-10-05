<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('judul', 'Presensi Prakerin')</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--b1:#3b5bf0;--b2:#8b5cf6;--nv:#0f1420;--gy:#64748b;--bd:#e3e6ef;--hj:#16a34a;--mr:#dc2626;--kn:#d97706;--bi:#2563eb}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,-apple-system,Segoe UI,Arial,sans-serif;color:var(--nv);background:#f6f7fb;padding-bottom:70px}
a{color:inherit}
.topnav{display:flex;align-items:center;gap:18px;padding:14px 22px;background:#fff;border-bottom:1px solid var(--bd)}
.topnav .brand{font-weight:800;color:var(--b1);font-size:15px}.topnav .brand span{color:#e11d2e}
.nav-desktop{display:none;gap:18px;margin-left:10px;font-size:13.5px;font-weight:600;color:var(--gy)}
.nav-desktop a.aktif{color:var(--b1)}
.btn-keluar{margin-left:auto;background:#f1f3f9;border:0;padding:8px 14px;border-radius:10px;font:600 12.5px Inter,sans-serif;cursor:pointer;color:var(--nv)}
main{max-width:560px;margin:0 auto;padding:20px 16px}
.nav-mobile{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--bd);display:flex}
.nav-mobile .item{flex:1;text-align:center;padding:9px 0 7px;text-decoration:none;color:#9aa1b4;font-size:10.5px}
.nav-mobile .item span{display:block;margin-top:2px}
.nav-mobile .item.aktif{color:var(--b1)}
@media(min-width:768px){
  body{padding-bottom:0}
  .nav-desktop{display:flex}
  .nav-mobile{display:none}
  main{max-width:620px;padding-top:30px}
}
.card{background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 24px -14px rgba(30,41,89,.18);margin-bottom:16px}
h1{font-size:21px;margin:0 0 4px}.sub{font-size:13px;color:var(--gy);margin:0 0 18px}
.btn{display:block;width:100%;padding:13px;border:0;border-radius:14px;background:linear-gradient(100deg,var(--b1),var(--b2));color:#fff;font:700 14.5px Inter,sans-serif;cursor:pointer;text-align:center;text-decoration:none}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-outline{display:block;width:100%;padding:13px;border:1.5px solid var(--bd);border-radius:14px;background:#fff;color:var(--nv);font:700 14.5px Inter,sans-serif;text-align:center;text-decoration:none;margin-top:10px}
.btn small,.btn-outline small{display:block;font-weight:500;font-size:11.5px;opacity:.85;margin-top:2px}
</style>
@stack('css')
</head>
<body>
<header class="topnav">
  <div class="brand"><span>new</span> armada</div>
  <nav class="nav-desktop">
    <a href="#">Beranda</a>
    <a href="#">Lowongan</a>
    <a href="{{ route('presensi.index') }}" class="{{ request()->routeIs('presensi.*') ? 'aktif' : '' }}">Presensi</a>
    <a href="#">Profil</a>
  </nav>
  <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
    @csrf
    <button class="btn-keluar" type="submit">Keluar</button>
  </form>
</header>

<main>
  @if (session('sukses'))<div class="card" style="background:#ecfdf3;color:#0f7a3a">{{ session('sukses') }}</div>@endif
  @if (session('info'))<div class="card" style="background:#eef2ff;color:var(--b1)">{{ session('info') }}</div>@endif
  @yield('konten')
</main>

<nav class="nav-mobile">
  <a href="#" class="item">🏠<span>Beranda</span></a>
  <a href="#" class="item">💼<span>Lowongan</span></a>
  <a href="{{ route('presensi.index') }}" class="item {{ request()->routeIs('presensi.*') ? 'aktif' : '' }}">📅<span>Presensi</span></a>
  <a href="#" class="item">👤<span>Profil</span></a>
</nav>
@stack('js')
<script>
// Chrome/Safari bisa memulihkan halaman lama dari cache back/forward (bfcache) tanpa bertanya ke server.
// Kalau itu terjadi, muat ulang supaya server memutuskan: masih login -> tampil, sudah logout -> ke halaman login.
window.addEventListener('pageshow', function (e) { if (e.persisted) { window.location.reload(); } });
</script>
</body>
</html>
