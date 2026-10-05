@extends('layouts.app')
@section('judul', 'Scan QR')

@push('css')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<style>
.head{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.back{width:34px;height:34px;border-radius:50%;border:1px solid var(--bd);background:#fff;cursor:pointer;font-size:16px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;color:var(--nv)}
.head h1{margin:0;font-size:19px}
.info{font-size:12.5px;color:var(--gy);line-height:1.5;margin:0 0 12px}
.notif{display:none;font-size:12.5px;padding:10px 12px;border-radius:12px;margin-bottom:12px}
.notif.berhasil{display:block;background:#e7faf1;color:#0f7a3a}.notif.gagal{display:block;background:#fef2f2;color:var(--mr)}.notif.proses{display:block;background:#eef2ff;color:var(--b1)}
.vp{position:relative;aspect-ratio:1/1;max-height:340px;width:100%;border-radius:18px;overflow:hidden;background:radial-gradient(#1b2035,#0b0e1a);display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;text-align:center;padding:20px}
.vp video,.vp img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.vp.selfie video{transform:scaleX(-1)}
.br{position:absolute;width:34px;height:34px;border:4px solid #4f7bff;pointer-events:none}
.br.ok{border-color:#22c55e}
.tl{top:16%;left:16%;border-right:0;border-bottom:0;border-radius:10px 0 0 0}.tr{top:16%;right:16%;border-left:0;border-bottom:0;border-radius:0 10px 0 0}
.bl{bottom:16%;left:16%;border-right:0;border-top:0;border-radius:0 0 0 10px}.brr{bottom:16%;right:16%;border-left:0;border-top:0;border-radius:0 0 10px 0}
.pill{display:table;margin:12px auto;font-size:11.5px;font-weight:600;color:var(--b1);background:#eef2ff;padding:6px 14px;border-radius:20px}
.tiles2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.tile2{border:1px solid var(--bd);border-radius:12px;padding:10px 12px}
.tile2 small{display:block;font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.04em;text-transform:uppercase}
.tile2 b{font:600 14px "Courier New",monospace}.tile2 b.baik{color:var(--hj)}.tile2 b.buruk{color:var(--mr)}
.sesi-pilih{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:4px}
.sc{border:2px solid var(--bd);background:#f4f6fb;border-radius:16px;padding:16px 8px;cursor:pointer;font:inherit;color:var(--gy)}
.sc.tersedia{border-color:var(--b1);background:#eef2ff;color:var(--nv)}
.sc .ic{font-size:28px}.sc b{display:block;font-size:13.5px;margin:4px 0}.sc small{font-size:11px}
.err{color:var(--mr);font-size:12.5px;margin-top:10px}
.cek{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#34d399,#16a34a);color:#fff;font-size:34px;line-height:64px;margin:0 auto 14px;text-align:center}
.pro{display:flex;align-items:center;gap:12px;background:#f4f6fb;border-radius:14px;padding:12px;margin:16px 0;text-align:left}
.pro .av{width:44px;height:44px;border-radius:10px;background:#dde1ee;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:10px;color:#94a3b8;flex-shrink:0}
.pro .av img{width:100%;height:100%;object-fit:cover}
.pro b{display:block;font-size:14px}.pro small{font-size:11.5px;color:var(--gy)}
</style>
@endpush

@section('konten')
<div id="vPilihSesi" class="card">
  <h1 style="margin-bottom:4px">Presensi Scan</h1>
  @unless ($hariWajib)
    <div class="notif berhasil" style="background:#fff7ed;color:#9a3412;margin-bottom:12px">Hari ini bukan hari kerja{{ $catatanHari ? ' (' . $catatanHari . ')' : '' }}. Presensi boleh dilakukan tetapi tidak wajib, dan akan menunggu persetujuan admin. Kalau absen sore, laporan harian tetap wajib diisi.</div>
  @endunless
  <p class="info">Silahkan pilih sesuai dengan jam.</p>
  <div class="sesi-pilih">
    <button type="button" class="sc" id="scPagi" onclick="pilihSesi('pagi')"><div class="ic">☀️</div><b>Presensi Pagi</b><small>06.00 - 13.00</small></button>
    <button type="button" class="sc" id="scSore" onclick="pilihSesi('sore')"><div class="ic">🌙</div><b>Presensi Sore</b><small>13.00 - 18.00</small></button>
  </div>
  <p class="info" style="margin-top:12px">Di luar jam tersebut (mis. departemen dengan shift berbeda), presensi tetap tercatat dan menunggu persetujuan HRD.</p>
</div>

<div id="vScan" class="card" style="display:none">
  <div class="head"><a href="{{ route('presensi.index') }}" class="back">←</a><h1>Scan QR</h1></div>
  <p class="info">Hadapkan Kartu ID (QR) ke kamera untuk absen. Anda harus berada di salah satu zona absen yang terdaftar.</p>
  <div class="notif" id="notif"></div>
  <div class="vp"><video id="videoScan" playsinline muted></video><canvas id="canvasScan" style="display:none"></canvas>
    <i class="br tl" id="b1"></i><i class="br tr" id="b2"></i><i class="br bl" id="b3"></i><i class="br brr" id="b4"></i></div>
  <div class="pill" id="scanStatus">Menyiapkan...</div>
  <div class="tiles2"><div class="tile2"><small id="tJarakL">Jarak ke titik</small><b id="tJarak">-</b></div>
  <div class="tile2"><small>Akurasi GPS</small><b id="tAkurasi">-</b></div></div>
</div>

<div id="vSelfie" class="card" style="display:none">
  <div class="head"><button type="button" class="back" onclick="ulangDariScan()">←</button><h1>Selfie Dokumentasi</h1></div>
  <p class="info">Ambil foto selfie untuk menyelesaikan absen. Anda harus tetap berada di zona absen saat mengambil foto.</p>
  <div class="vp selfie" id="vpSelfie"><video id="video" playsinline muted style="display:none"></video><img id="preview" style="display:none" alt="">
    <span id="ph">Tekan tombol di bawah untuk mengaktifkan kamera.</span></div>
  <p class="info" id="selfieInfo" style="text-align:center;margin:10px 0">Posisikan wajah Anda, lalu ambil foto.</p>
  <button class="btn" id="btnSelfie" type="button" onclick="aksiSelfie()">Aktifkan Kamera</button>
  <button class="btn-outline" id="btnUlang" type="button" style="display:none" onclick="ulangFoto()">Ulangi Foto</button>
  <canvas id="canvasFoto" style="display:none"></canvas>
  <div class="err" id="errSelfie"></div>
</div>

<div class="modal" id="mOk" style="position:fixed;inset:0;background:rgba(20,25,50,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:50">
  <div style="background:#fff;border-radius:22px;padding:24px;width:100%;max-width:360px;text-align:center">
    <div class="cek">✓</div><h2 style="margin:0 0 6px;font-size:17px">Presensi Berhasil Tercatat</h2>
    <p class="info" id="okInfo" style="margin:0">Kehadiranmu hari ini sudah berhasil dicatat oleh sistem.</p>
    <div class="pro"><div class="av" id="okAv">FOTO</div><div><b id="okNama">-</b><small id="okWaktu">-</small></div></div>
    <a href="{{ route('presensi.index') }}" class="btn">Selesai</a>
  </div>
</div>

<script>
const POSISI = @json($posisi);
const JENDELA = @json($jendela);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = id => document.getElementById(id);
const p2 = n => String(n).padStart(2, '0');
const BULAN_NAMA = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

let deviceId = localStorage.getItem('absen_device_id');
if (!deviceId) { deviceId = 'dev_' + Math.random().toString(36).substring(2, 10); localStorage.setItem('absen_device_id', deviceId); }

function headerJson() { return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }; }

// ===== Pilih sesi =====
let sesiAktif = null;
function dalamJam(k) { const d = new Date(), h = d.getHours() + d.getMinutes()/60; return h >= JENDELA[k].mulai && h < JENDELA[k].selesai; }
(function tandaiSesiTersedia() {
  ['pagi','sore'].forEach(k => $('sc'+(k==='pagi'?'Pagi':'Sore')).classList.toggle('tersedia', dalamJam(k)));
})();
function pilihSesi(k) {
  sesiAktif = k;
  $('vPilihSesi').style.display = 'none'; $('vScan').style.display = 'block';
  $('scanStatus').textContent = 'Meminta izin lokasi...';
  const lanjut = () => { mulaiTracking(); mulaiScan(); };
  navigator.geolocation.getCurrentPosition(lanjut, lanjut, { enableHighAccuracy: true, timeout: 8000 });
}

// ===== GPS =====
const MAX_AKURASI = {{ config('prakerin.maks_akurasi_meter') }}, SMOOTH = 0.4; // sama dengan batas di server (config/prakerin.php)
let userLat = null, userLng = null, akurasi = null, bufer = null, watchId = null, tileTimer = null;
function jarak(a,b,c,d){const R=6371e3,x=(c-a)*Math.PI/180,y=(d-b)*Math.PI/180;const h=Math.sin(x/2)**2+Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(y/2)**2;return R*2*Math.atan2(Math.sqrt(h),Math.sqrt(1-h));}
const cm = m => `${(m*100).toFixed(0)} cm`;
function posTerdekat(){ if(userLat===null) return null; let t=null; for(const p of POSISI){const j=jarak(p.lat,p.lng,userLat,userLng); if(!t||j<t.jarak) t={pos:p,jarak:j};} return t; }
function mulaiTracking() {
  if (watchId !== null || !navigator.geolocation) return;
  watchId = navigator.geolocation.watchPosition(pos => {
    const now = Date.now(), a = pos.coords.accuracy;
    if (!bufer || now - bufer.t > 10000 || a < bufer.a) bufer = { a, t: now };
    akurasi = bufer.a;
    if (userLat === null) { userLat = pos.coords.latitude; userLng = pos.coords.longitude; }
    else { userLat += SMOOTH*(pos.coords.latitude-userLat); userLng += SMOOTH*(pos.coords.longitude-userLng); }
  }, e => console.warn('GPS:', e.message), { enableHighAccuracy: true, timeout: 3000, maximumAge: 700 });
}
function perbaruiTile() {
  const t = posTerdekat();
  if (t) { $('tJarakL').textContent = 'Jarak ke ' + t.pos.nama; $('tJarak').textContent = cm(t.jarak); $('tJarak').className = t.jarak <= t.pos.radius ? 'baik' : ''; }
  if (akurasi !== null) { const ok = akurasi <= MAX_AKURASI; $('tAkurasi').textContent = akurasi.toFixed(1) + ' m' + (ok ? '' : ' (Kurang)'); $('tAkurasi').className = ok ? 'baik' : 'buruk'; }
}

// ===== Scan QR =====
const BATAS_TUNGGU_MS = 6000;
let streamScan = null, scanning = false, memproses = false, target = null, ident = null, waktuScan = null;
function notif(j,t){ $('notif').className='notif '+j; $('notif').textContent=t; }
function bracket(ok){ ['b1','b2','b3','b4'].forEach(i=>$(i).classList.toggle('ok', ok)); }
function mulaiScan() {
  scanning = true; memproses = false; bracket(false); $('notif').className = 'notif';
  clearInterval(tileTimer); tileTimer = setInterval(perbaruiTile, 500);
  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false }).then(s => {
    streamScan = s; $('videoScan').srcObject = s; $('videoScan').play(); $('scanStatus').textContent = 'Arahkan Kartu ID ke kamera...'; requestAnimationFrame(frame);
  }).catch(() => { $('scanStatus').textContent = 'Kamera tidak bisa diakses. Aktifkan izin kamera.'; });
}
function stopStreamScan(){ if(streamScan){streamScan.getTracks().forEach(t=>t.stop()); streamScan=null;} }
function frame() {
  if (!scanning) return;
  const v = $('videoScan');
  if (v.readyState === v.HAVE_ENOUGH_DATA && !memproses) {
    const c = $('canvasScan'); c.width = v.videoWidth; c.height = v.videoHeight;
    const x = c.getContext('2d', { willReadFrequently: true }); x.drawImage(v, 0, 0, c.width, c.height);
    const d = x.getImageData(0, 0, c.width, c.height);
    const k = jsQR(d.data, d.width, d.height, { inversionAttempts: 'dontInvert' });
    if (k && k.data) { memproses = true; prosesQR(String(k.data).trim()); }
  }
  requestAnimationFrame(frame);
}
function ulangScan(pesan, ms){ notif('gagal', pesan); setTimeout(() => { $('notif').className = 'notif'; memproses = false; $('scanStatus').textContent = 'Arahkan Kartu ID ke kamera...'; }, ms); }
function prosesQR(uuid) {
  if (!uuid) return ulangScan('Kartu ID tidak terbaca dengan benar.', 2200);
  notif('proses', 'Kartu ID terbaca. Memastikan identitas & lokasi Anda...'); $('scanStatus').textContent = 'Memvalidasi...';
  const mulai = Date.now();
  const cek = setInterval(() => {
    const cukup = userLat !== null && akurasi !== null && akurasi <= MAX_AKURASI;
    if (cukup || Date.now() - mulai > BATAS_TUNGGU_MS) { clearInterval(cek); validasi(uuid, cukup); }
  }, 400);
}
function validasi(uuid, cukup) {
  if (userLat === null) return ulangScan('Lokasi GPS tidak ditemukan. Coba lagi.', 2400);
  if (!cukup) return ulangScan(`Akurasi GPS kurang baik (${akurasi.toFixed(1)} m). Coba lagi di tempat lebih terbuka.`, 2600);
  const t = posTerdekat();
  if (!t || t.jarak > t.pos.radius) return ulangScan('Anda tidak berada di salah satu zona absen yang terdaftar.', 2600);

  fetch('{{ route('presensi.verifikasi') }}', { method: 'POST', headers: headerJson(), body: JSON.stringify({ uuid }) })
    .then(r => r.json()).then(d => {
      if (!d.valid) return ulangScan(d.pesan || 'Kartu ID tidak dikenali.', 2600);
      ident = { uuid, nama: d.nama }; target = t.pos; waktuScan = new Date();
      scanning = false; bracket(true);
      notif('berhasil', `Absen berhasil, ${d.nama.toUpperCase()}! Lokasi: ${t.pos.nama}.`);
      setTimeout(() => { stopStreamScan(); clearInterval(tileTimer); sesiAktif === 'pagi' ? bukaSelfie() : kirimSoreLalu(); }, 1400);
    }).catch(() => ulangScan('Tidak bisa menghubungi server untuk verifikasi.', 2600));
}
function ulangDariScan() { $('vSelfie').style.display='none'; $('vScan').style.display='block'; ident=null; target=null; mulaiScan(); }

// ===== Sesi SORE: simpan presensi, lalu pindah ke halaman laporan harian =====
function kirimSoreLalu() {
  fetch('{{ route('presensi.simpan') }}', {
    method: 'POST', headers: headerJson(),
    body: JSON.stringify({ uuid: ident.uuid, sesi: 'sore', lat: userLat, lng: userLng, akurasi, deviceId })
  }).then(r => r.json()).then(d => {
    if (!d.ok) {
      alert('Gagal: ' + (d.pesan || 'tidak diketahui'));
      if (d.laporanUrl) { window.location.href = d.laporanUrl; return; } // presensi sore sudah ada, laporan belum diisi
      return ulangDariScan();
    }
    window.location.href = '{{ route('laporan.form', ['sesiPresensi' => '__ID__']) }}'.replace('__ID__', d.sesiPresensiId);
  }).catch(() => { alert('Tidak bisa terhubung ke server.'); ulangDariScan(); });
}

// ===== Sesi PAGI: selfie =====
let streamCam = null, foto = null, tahap = 'idle';
function bukaSelfie() {
  $('vScan').style.display='none'; $('vSelfie').style.display='block';
  tahap = 'idle'; foto = null; $('video').style.display='none'; $('preview').style.display='none'; $('ph').style.display='block';
  $('btnSelfie').textContent='Aktifkan Kamera'; $('btnSelfie').disabled=false; $('btnUlang').style.display='none'; $('errSelfie').textContent='';
}
function tutupKamera(){ if(streamCam){streamCam.getTracks().forEach(t=>t.stop()); streamCam=null;} }
function masihDiZona() {
  const j = jarak(target.lat, target.lng, userLat, userLng);
  if (j > target.radius) { $('errSelfie').textContent = `Kamu sudah keluar dari zona absen (jarak ${cm(j)}). Ulangi dari scan QR.`; return false; }
  return true;
}
function aksiSelfie() {
  $('errSelfie').textContent = '';
  if (tahap === 'idle') {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }).then(s => {
      streamCam = s; $('video').srcObject = s; $('video').play(); $('video').style.display='block'; $('ph').style.display='none';
      tahap = 'live'; $('btnSelfie').textContent = '📸 Ambil Foto';
    }).catch(() => { $('errSelfie').textContent = 'Kamera tidak bisa diakses. Pastikan izin kamera diaktifkan.'; });
  } else if (tahap === 'live') {
    if (!masihDiZona()) return;
    const v = $('video'), c = $('canvasFoto'); c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0, c.width, c.height); foto = c.toDataURL('image/jpeg', 0.9);
    $('preview').src = foto; $('preview').style.display='block'; $('video').style.display='none'; tutupKamera();
    tahap = 'preview'; $('btnSelfie').textContent = '✓ Gunakan Foto & Kirim Presensi'; $('btnUlang').style.display='block';
  } else if (tahap === 'preview') {
    if (!masihDiZona()) return;
    $('btnSelfie').disabled = true; $('btnSelfie').textContent = 'Mengirim...';
    fetch('{{ route('presensi.simpan') }}', {
      method: 'POST', headers: headerJson(),
      body: JSON.stringify({ uuid: ident.uuid, sesi: 'pagi', lat: userLat, lng: userLng, akurasi, deviceId, fotoBase64: foto })
    }).then(r => r.json()).then(d => {
      $('btnSelfie').disabled = false; $('btnSelfie').textContent = '✓ Gunakan Foto & Kirim Presensi';
      if (!d.ok) { $('errSelfie').textContent = 'Gagal: ' + (d.pesan || 'tidak diketahui'); return; }
      const w = new Date();
      $('okNama').textContent = d.nama;
      $('okInfo').textContent = d.statusPersetujuan === 'menunggu'
        ? 'Presensi tercatat dan menunggu persetujuan admin.'
        : 'Kehadiranmu hari ini sudah berhasil dicatat oleh sistem.';
      $('okWaktu').textContent = `Tercatat ${w.getDate()} ${BULAN_NAMA[w.getMonth()]} ${w.getFullYear()}, ${p2(w.getHours())}:${p2(w.getMinutes())} WIB`;
      $('okAv').innerHTML = d.fotoUrl ? `<img src="${d.fotoUrl}" alt="">` : 'FOTO';
      $('mOk').style.display = 'flex';
    }).catch(() => { $('btnSelfie').disabled = false; $('btnSelfie').textContent = '✓ Gunakan Foto & Kirim Presensi'; $('errSelfie').textContent = 'Tidak bisa terhubung ke server.'; });
  }
}
function ulangFoto() { bukaSelfie(); }
</script>
@endsection
