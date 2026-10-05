<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('judul', 'Portal Resmi Prakerin') - New Armada Group</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--b1:#3b5bf0;--b2:#8b5cf6;--nv:#0f1420;--gy:#64748b;--bd:#e3e6ef}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,-apple-system,Segoe UI,Arial,sans-serif;color:var(--nv);min-height:100vh}
.shell{display:grid;grid-template-columns:1fr 1fr;min-height:100vh}

.brand{position:relative;overflow:hidden;background:#f5f7fd;padding:44px 52px;display:flex;flex-direction:column}
.blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.55;pointer-events:none}
.blob.b1{width:340px;height:340px;background:#7fa0ff;top:-90px;left:-90px}
.blob.b2{width:300px;height:300px;background:#ffb0c4;bottom:-100px;right:-60px}
.logo{display:flex;align-items:center;gap:10px;position:relative;z-index:1}
.logo .chip{background:#fff;border-radius:10px;padding:5px 9px;font-size:11px;font-weight:800;line-height:1.1;box-shadow:0 4px 10px rgba(0,0,0,.06)}
.logo .chip span{display:block}
.logo .chip .n1{color:#e11d2e}.logo .chip .n2{color:var(--nv)}
.logo b{font-size:15px}.logo small{display:block;font-size:10.5px;color:var(--b1);font-weight:700}

.brand-mid{position:relative;z-index:1;margin-top:auto;padding-bottom:8px}
.badge{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;color:var(--b1);background:#e8edff;padding:6px 12px;border-radius:20px;margin-bottom:18px}
.badge i{width:6px;height:6px;border-radius:50%;background:var(--b1)}
.brand h1{font-size:32px;line-height:1.25;margin:0 0 14px;font-weight:800}
.brand h1 .grad{background:linear-gradient(100deg,var(--b1),var(--b2));-webkit-background-clip:text;background-clip:text;color:transparent}
.brand p{font-size:13.5px;color:var(--gy);line-height:1.6;max-width:400px;margin:0 0 22px}
.feat{display:flex;gap:12px}
.feat .f{flex:1;background:#fff;border:1px solid var(--bd);border-radius:14px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start}
.feat .ic{font-size:16px}.feat b{display:block;font-size:12.5px}.feat small{font-size:11px;color:var(--gy)}
.brand-foot{position:relative;z-index:1;border-top:1px solid var(--bd);padding-top:14px;margin-top:26px;display:flex;justify-content:space-between;font-size:11.5px;color:#94a3b8}
.brand-foot a{color:var(--gy);text-decoration:none}

.formside{background:#fff;display:flex;align-items:center;justify-content:center;padding:40px}
.formwrap{width:100%;max-width:380px}
.formwrap h2{font-size:22px;margin:0 0 4px}
.formwrap .sub{font-size:13px;color:var(--gy);margin:0 0 26px}
.fld{margin-bottom:16px}
.fld label{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;font-weight:700;margin-bottom:7px}
.fld a{font-size:11.5px;font-weight:600;color:var(--b1);text-decoration:none}
.inp{position:relative}
.inp span{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#a3aabb;font-size:14px}
.inp input{width:100%;padding:12px 14px 12px 36px;border:1.5px solid var(--bd);border-radius:11px;font:14px Inter,sans-serif;background:#fafbfd}
.inp input:focus{outline:0;border-color:var(--b1);background:#fff}
.inp input.error{border-color:#dc2626}
.errtxt{font-size:11.5px;color:#dc2626;margin-top:5px}
.chk{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--gy);margin:-4px 0 18px}
.chk input{width:15px;height:15px}
.btn{width:100%;padding:13px;border:0;border-radius:12px;background:linear-gradient(100deg,var(--b1),var(--b2));color:#fff;font:700 14.5px Inter,sans-serif;cursor:pointer}
.btn:disabled{opacity:.55;cursor:not-allowed}
.hr{display:flex;align-items:center;gap:10px;margin:22px 0 16px;color:#c9cedb;font-size:11px}
.hr::before,.hr::after{content:'';flex:1;height:1px;background:var(--bd)}
.switch{text-align:center;font-size:13px;color:var(--gy)}
.switch a{color:var(--b1);font-weight:700;text-decoration:none}
.msg{font-size:12.5px;border-radius:10px;padding:9px 12px;margin-bottom:14px}
.msg.gagal{background:#fef2f2;color:#dc2626}
.msg.ok{background:#ecfdf3;color:#0f7a3a}

.back{display:none;width:32px;height:32px;border-radius:50%;border:1px solid var(--bd);background:#fff;align-items:center;justify-content:center;margin-bottom:14px;text-decoration:none;color:var(--nv)}
.landing-btns{display:none;flex-direction:column;gap:10px;position:relative;z-index:1;margin-top:22px}
.landing-btns a{display:block;text-align:center;padding:13px;border-radius:12px;font:700 14px Inter,sans-serif;text-decoration:none;border:1.5px solid transparent}
.landing-btns .lb-masuk{background:linear-gradient(100deg,var(--b1),var(--b2));color:#fff}
.landing-btns .lb-daftar{background:#fff;border-color:var(--bd);color:var(--nv)}

@media (max-width: 860px){
  .shell{grid-template-columns:1fr;min-height:auto}
  .brand{padding:32px 24px 26px;min-height:100vh}
  .brand h1{font-size:26px}
  .feat{flex-direction:column}
  .formside{padding:28px 22px}
  .brand-mid{margin-top:34px}

  /* Landing (root "/"): tampil tombol Login/Sign Up, form disembunyikan */
  body.mode-landing .formside{display:none}
  body.mode-landing .landing-btns{display:flex}

  /* Halaman daftar/masuk langsung (mobile): brand ringkas + tombol kembali, form tampil */
  body.mode-form .brand{min-height:auto;padding-bottom:22px}
  body.mode-form .brand-mid,body.mode-form .brand-foot,body.mode-form .feat{display:none}
  body.mode-form .back{display:flex}
}
</style>
@stack('css')
</head>
<body class="@yield('body-class')">
<div class="shell">
  <div class="brand">
    <div class="blob b1"></div><div class="blob b2"></div>
    <div class="logo">
      <div class="chip"><span class="n1">new</span><span class="n2">armada</span></div>
      <div><b>New Armada</b><small>PT MEKAR ARMADA JAYA</small></div>
    </div>

    <a href="{{ route('landing') }}" class="back">&larr;</a>

    <div class="brand-mid">
      <div class="badge"><i></i> @yield('badge', 'Portal Pendaftaran')</div>
      <h1>Selamat Datang di<br><span class="grad">Portal Resmi Prakerin</span></h1>
      <p>Akses portal akun peserta untuk memantau status lamaran, verifikasi berkas sekolah/kampus, dan alokasi periode pengerjaan.</p>
      <div class="feat">
        <div class="f"><span class="ic">🛡️</span><div><b>Proses Realtime</b><small>Status seleksi transparan</small></div></div>
        <div class="f"><span class="ic">🔒</span><div><b>Aman &amp; Terpusat</b><small>Verifikasi terintegrasi</small></div></div>
      </div>
    </div>

    <div class="landing-btns">
      <a href="{{ route('login') }}" class="lb-masuk">Login</a>
      <a href="{{ route('daftar') }}" class="lb-daftar">Sign Up</a>
    </div>

    <div class="brand-foot"><span>© {{ date('Y') }} PT MAI</span><a href="#">Butuh bantuan?</a></div>
  </div>

  <div class="formside">
    <div class="formwrap">
      @if (session('sukses'))
        <div class="msg ok">{{ session('sukses') }}</div>
      @endif
      @if ($errors->any())
        <div class="msg gagal">{{ $errors->first() }}</div>
      @endif

      @yield('konten')
    </div>
  </div>
</div>
<script>
// Chrome/Safari bisa memulihkan halaman lama dari cache back/forward (bfcache) tanpa bertanya ke server.
// Kalau itu terjadi, muat ulang supaya server memutuskan: masih login -> tampil, sudah logout -> ke halaman login.
window.addEventListener('pageshow', function (e) { if (e.persisted) { window.location.reload(); } });
</script>
</body>
</html>
