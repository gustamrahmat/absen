<?php $__env->startSection('judul', 'Scan QR'); ?>

<?php $__env->startPush('css'); ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<style>
/* ===== UMUM ===== */
.head{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.back{width:34px;height:34px;border-radius:50%;border:1px solid var(--bd);background:#fff;cursor:pointer;font-size:16px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;color:var(--nv)}
.head h1{margin:0;font-size:19px}
.info{font-size:12.5px;color:var(--gy);line-height:1.5;margin:0 0 12px}
.notif{display:none;font-size:12.5px;padding:10px 12px;border-radius:12px;margin-bottom:12px}
.notif.berhasil{display:block;background:#e7faf1;color:#0f7a3a}
.notif.gagal{display:block;background:#fef2f2;color:var(--mr)}
.notif.proses{display:block;background:#eef2ff;color:var(--b1)}

/* ===== POPUP PILIH SESI ===== */
.popup-sesi{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.55);backdrop-filter:blur(5px)}
.popup-sesi-box{width:100%;max-width:460px;background:#fff;border-radius:24px;padding:28px;box-shadow:0 24px 70px rgba(15,23,42,.22);animation:popupMasuk .2s ease-out}
@keyframes popupMasuk{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.popup-main-icon{width:64px;height:64px;margin:0 auto 14px;border-radius:20px;background:#eef2ff;display:flex;align-items:center;justify-content:center;font-size:32px}
.popup-sesi-box h2{margin:0 0 6px;text-align:center;color:#0f172a;font-size:21px;font-weight:700}
.subjudul{margin:0 0 20px;text-align:center;color:#64748b;font-size:13px;line-height:1.5}

/* ===== KARTU SESI ===== */
.sesi-pilih{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.sc{position:relative;min-height:150px;border:1.5px solid #e2e8f0;border-radius:18px;background:#f8fafc;padding:20px 12px;font:inherit;cursor:pointer;color:#475569;transition:transform .18s,border-color .18s,background .18s,box-shadow .18s}
.sc .ic{width:50px;height:50px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;border-radius:15px;background:#fff;font-size:27px;box-shadow:0 3px 10px rgba(15,23,42,.06)}
.sc b{display:block;margin-bottom:5px;font-size:14px;font-weight:700}
.sc small{font-size:11px;color:inherit}
.sc.tersedia{border:2px solid var(--b1);background:#eef2ff;color:#0f172a;box-shadow:0 8px 24px rgba(79,70,229,.10)}
.sc.tersedia:hover{transform:translateY(-2px);background:#e8edff}
.sc.tidak-tersedia{background:#f8fafc;border-color:#e2e8f0;color:#94a3b8;opacity:.75;cursor:not-allowed}

/* lencana status di pojok kartu */
.badge-sesi{position:absolute;top:10px;right:10px;padding:4px 8px;border-radius:999px;font-size:9px;font-weight:700;text-transform:uppercase;background:#eef2f7;color:#94a3b8}
.badge-sesi.buka{background:#dcfce7;color:#15803d}
.badge-sesi.tutup{background:#f1f5f9;color:#94a3b8}

/* kotak status di bawah kartu */
.status-sesi{margin-top:16px;padding:12px 14px;border-radius:13px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:12px;text-align:center;line-height:1.5}
.status-sesi.buka{background:#ecfdf5;border-color:#bbf7d0;color:#15803d}
.status-sesi.tutup{background:#fff7ed;border-color:#fed7aa;color:#c2410c}

/* ===== POPUP BELUM WAKTUNYA ===== */
.popup-info{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:1100}
.popup-info-box{width:100%;max-width:370px;background:#fff;border-radius:22px;padding:26px;text-align:center;box-shadow:0 24px 70px rgba(15,23,42,.22);animation:popupMasuk .2s ease-out}
.popup-icon{width:62px;height:62px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;background:#fff7ed;color:#d97706;font-size:28px}
.popup-info-box h3{margin:0 0 7px;color:#0f172a;font-size:17px}
.popup-info-box p{margin:0 0 18px;color:#64748b;font-size:13px;line-height:1.6}
.popup-btn{width:100%;border:0;border-radius:13px;padding:12px;background:var(--b1);color:#fff;font-weight:700;cursor:pointer}

/* ===== KAMERA SCAN ===== */
.vp{position:relative;aspect-ratio:1/1;max-height:340px;width:100%;border-radius:18px;overflow:hidden;background:radial-gradient(#1b2035,#0b0e1a);display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;text-align:center;padding:20px}
.vp video,.vp img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.vp.selfie video{transform:scaleX(-1)}
.br{position:absolute;width:34px;height:34px;border:4px solid #4f7bff;pointer-events:none}
.br.ok{border-color:#22c55e}
.tl{top:16%;left:16%;border-right:0;border-bottom:0;border-radius:10px 0 0 0}
.tr{top:16%;right:16%;border-left:0;border-bottom:0;border-radius:0 10px 0 0}
.bl{bottom:16%;left:16%;border-right:0;border-top:0;border-radius:0 0 0 10px}
.brr{bottom:16%;right:16%;border-left:0;border-top:0;border-radius:0 0 10px 0}
.pill{display:table;margin:12px auto;font-size:11.5px;font-weight:600;color:var(--b1);background:#eef2ff;padding:6px 14px;border-radius:20px}
.tiles2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.tile2{border:1px solid var(--bd);border-radius:12px;padding:10px 12px}
.tile2 small{display:block;font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.04em;text-transform:uppercase}
.tile2 b{font:600 14px "Courier New",monospace}
.tile2 b.baik{color:var(--hj)}
.tile2 b.buruk{color:var(--mr)}
.err{color:var(--mr);font-size:12.5px;margin-top:10px}
.cek{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#34d399,#16a34a);color:#fff;font-size:34px;line-height:64px;margin:0 auto 14px;text-align:center}
.pro{display:flex;align-items:center;gap:12px;background:#f4f6fb;border-radius:14px;padding:12px;margin:16px 0;text-align:left}
.pro .av{width:44px;height:44px;border-radius:10px;background:#dde1ee;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:10px;color:#94a3b8;flex-shrink:0}
.pro .av img{width:100%;height:100%;object-fit:cover}
.pro b{display:block;font-size:14px}
.pro small{font-size:11.5px;color:var(--gy)}

/* ===== LAYAR KECIL ===== */
@media(max-width:520px){
  .popup-sesi{padding:14px}
  .popup-sesi-box{padding:22px 16px;border-radius:21px}
  .sesi-pilih{grid-template-columns:1fr}
  .sc{min-height:120px;display:grid;grid-template-columns:60px 1fr;align-items:center;text-align:left;padding:14px}
  .sc .ic{grid-row:span 2;margin:0 auto}
  .sc b{margin:0}
  .sc small{display:block}
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('konten'); ?>


<div id="vPilihSesi" class="popup-sesi">
  <div class="popup-sesi-box">
    <div class="popup-main-icon">🕐</div>
    <h2>Pilih Waktu Presensi</h2>
    <p class="subjudul">Pilih sesi presensi sesuai dengan waktu yang sedang berlangsung.</p>

    <div class="sesi-pilih">
      <button type="button" class="sc" id="scPagi" onclick="pilihSesi('pagi')">
        <span class="badge-sesi" id="badgePagi">Memeriksa</span>
        <div class="ic">☀️</div>
        <b>Presensi Pagi</b>
        <small>06.00 - 13.00 WIB</small>
      </button>

      <button type="button" class="sc" id="scSore" onclick="pilihSesi('sore')">
        <span class="badge-sesi" id="badgeSore">Memeriksa</span>
        <div class="ic">🌙</div>
        <b>Presensi Sore</b>
        <small>13.00 - 18.00 WIB</small>
      </button>
    </div>

    <div class="status-sesi" id="statusSesi">Memeriksa waktu presensi...</div>
  </div>
</div>


<div id="popupBelumWaktunya" class="popup-info">
  <div class="popup-info-box">
    <div class="popup-icon">⏰</div>
    <h3 id="popupJudul">Presensi belum tersedia</h3>
    <p id="popupPesan">Sesi presensi belum dapat digunakan saat ini.</p>
    <button type="button" class="popup-btn" onclick="tutupPopupWaktu()">Mengerti</button>
  </div>
</div>


<div id="vScan" class="card" style="display:none">
  <div class="head">
    <a href="<?php echo e(route('presensi.index')); ?>" class="back">←</a>
    <h1>Scan QR</h1>
  </div>
  <p class="info">Hadapkan Kartu ID (QR) ke kamera untuk absen. Anda harus berada di salah satu zona absen yang terdaftar.</p>
  <div class="notif" id="notif"></div>
  <div class="vp">
    <video id="videoScan" playsinline muted></video>
    <canvas id="canvasScan" style="display:none"></canvas>
    <i class="br tl" id="b1"></i><i class="br tr" id="b2"></i><i class="br bl" id="b3"></i><i class="br brr" id="b4"></i>
  </div>
  <div class="pill" id="scanStatus">Menyiapkan...</div>
  <div class="tiles2">
    <div class="tile2"><small id="tJarakL">Jarak ke titik</small><b id="tJarak">-</b></div>
    <div class="tile2"><small>Akurasi GPS</small><b id="tAkurasi">-</b></div>
  </div>
</div>


<div id="vSelfie" class="card" style="display:none">
  <div class="head">
    <button type="button" class="back" onclick="ulangDariScan()">←</button>
    <h1>Selfie Dokumentasi</h1>
  </div>
  <p class="info">Ambil foto selfie untuk menyelesaikan absen. Anda harus tetap berada di zona absen saat mengambil foto.</p>
  <div class="vp selfie" id="vpSelfie">
    <video id="video" playsinline muted style="display:none"></video>
    <img id="preview" style="display:none" alt="">
    <span id="ph">Tekan tombol di bawah untuk mengaktifkan kamera.</span>
  </div>
  <p class="info" id="selfieInfo" style="text-align:center;margin:10px 0">Posisikan wajah Anda, lalu ambil foto.</p>
  <button class="btn" id="btnSelfie" type="button" onclick="aksiSelfie()">Aktifkan Kamera</button>
  <button class="btn-outline" id="btnUlang" type="button" style="display:none" onclick="ulangFoto()">Ulangi Foto</button>
  <canvas id="canvasFoto" style="display:none"></canvas>
  <div class="err" id="errSelfie"></div>
</div>


<div class="modal" id="mOk" style="position:fixed;inset:0;background:rgba(20,25,50,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:50">
  <div style="background:#fff;border-radius:22px;padding:24px;width:100%;max-width:360px;text-align:center">
    <div class="cek">✓</div>
    <h2 style="margin:0 0 6px;font-size:17px">Presensi Berhasil Tercatat</h2>
    <p class="info" style="margin:0">Kehadiranmu hari ini sudah berhasil dicatat oleh sistem.</p>
    <div class="pro">
      <div class="av" id="okAv">FOTO</div>
      <div><b id="okNama">-</b><small id="okWaktu">-</small></div>
    </div>
    <a href="<?php echo e(route('presensi.index')); ?>" class="btn">Selesai</a>
  </div>
</div>


<div id="scanConfig" style="display:none"
     data-posisi="<?php echo e(json_encode($posisi)); ?>"
     data-jendela="<?php echo e(json_encode($jendela)); ?>"
     data-max-akurasi="<?php echo e(config('prakerin.maks_akurasi_meter')); ?>"
     data-server-time="<?php echo e(now()->getTimestampMs()); ?>"
     data-offset-menit="<?php echo e(now()->utcOffset()); ?>"
     data-route-verifikasi="<?php echo e(route('presensi.verifikasi')); ?>"
     data-route-simpan="<?php echo e(route('presensi.simpan')); ?>"
     data-route-laporan="<?php echo e(route('laporan.form', ['sesiPresensi' => '__ID__'])); ?>"></div>

<script>
const cfg = document.getElementById('scanConfig');
const POSISI = JSON.parse(cfg.dataset.posisi);
const JENDELA = JSON.parse(cfg.dataset.jendela);
const MAX_AKURASI = Number(cfg.dataset.maxAkurasi);
const ROUTE_VERIFIKASI = cfg.dataset.routeVerifikasi;
const ROUTE_SIMPAN = cfg.dataset.routeSimpan;
const ROUTE_LAPORAN = cfg.dataset.routeLaporan;
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const $ = id => document.getElementById(id);
const p2 = n => String(n).padStart(2, '0');
const BULAN_NAMA = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

let deviceId = localStorage.getItem('absen_device_id');
if (!deviceId) { deviceId = 'dev_' + Math.random().toString(36).substring(2, 10); localStorage.setItem('absen_device_id', deviceId); }

function headerJson() { return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }; }

/* ===== WAKTU =====
   Jam diambil dari SERVER (bukan jam HP) dan dibaca dalam zona waktu aplikasi (WIB),
   jadi hasilnya sama walaupun zona waktu atau jam HP peserta berbeda. */
const SELISIH_SERVER = Number(cfg.dataset.serverTime) - Date.now();
const OFFSET_MS = Number(cfg.dataset.offsetMenit) * 60000;

// Date yang HARUS dibaca dengan getUTC* (hasilnya = jam dinding WIB)
function waktuWIB() { return new Date(Date.now() + SELISIH_SERVER + OFFSET_MS); }

function jamSekarang() {
  const d = waktuWIB();
  return d.getUTCHours() + d.getUTCMinutes() / 60 + d.getUTCSeconds() / 3600;
}

function dalamJam(sesi) {
  const jam = jamSekarang();
  return jam >= Number(JENDELA[sesi].mulai) && jam < Number(JENDELA[sesi].selesai);
}

function formatJam(angka) {
  angka = Number(angka);
  if (isNaN(angka)) return '--:--';
  let j = Math.floor(angka), m = Math.round((angka - j) * 60);
  if (m >= 60) { j += 1; m = 0; }
  return p2(j) + ':' + p2(m);
}

/* ===== STATUS KARTU PILIH SESI ===== */
function setKartu(sesi, buka) {
  const nama = sesi === 'pagi' ? 'Pagi' : 'Sore';
  const kartu = $('sc' + nama), badge = $('badge' + nama);
  kartu.classList.toggle('tersedia', buka);
  kartu.classList.toggle('tidak-tersedia', !buka);
  kartu.setAttribute('aria-disabled', String(!buka));  // sengaja TIDAK disabled, supaya tap tetap memunculkan penjelasan
  badge.textContent = buka ? 'Sedang Buka' : 'Tutup';
  badge.className = 'badge-sesi ' + (buka ? 'buka' : 'tutup');
}

function perbaruiSesi() {
  const pagiBuka = dalamJam('pagi'), soreBuka = dalamJam('sore');
  const status = $('statusSesi');

  setKartu('pagi', pagiBuka);
  setKartu('sore', soreBuka);

  if (pagiBuka || soreBuka) {
    status.className = 'status-sesi buka';
    status.innerHTML = '🟢 <b>Presensi ' + (pagiBuka ? 'Pagi' : 'Sore') + ' sedang dibuka.</b><br>Silakan pilih Presensi ' + (pagiBuka ? 'Pagi' : 'Sore') + '.';
  } else if (jamSekarang() < Number(JENDELA.pagi.mulai)) {
    status.className = 'status-sesi tutup';
    status.innerHTML = '⏰ Presensi belum dibuka.<br>Presensi Pagi mulai pukul ' + formatJam(JENDELA.pagi.mulai) + ' WIB.';
  } else {
    status.className = 'status-sesi tutup';
    status.innerHTML = '🔒 Presensi hari ini sudah ditutup.<br>Presensi terakhir sampai pukul ' + formatJam(JENDELA.sore.selesai) + ' WIB.';
  }
}

function perbaruiSesiAman() {
  try { perbaruiSesi(); }
  catch (e) {
    console.error('Gagal memeriksa waktu presensi:', e);
    const s = $('statusSesi');
    s.className = 'status-sesi tutup';
    s.textContent = 'Gagal memeriksa waktu presensi. Muat ulang halaman.';
  }
}
perbaruiSesiAman();
setInterval(perbaruiSesiAman, 15000);

/* ===== PILIH SESI ===== */
let sesiAktif = null;

function pilihSesi(sesi) {
  // Cek ulang saat tombol ditekan (halaman bisa sudah terbuka lama)
  if (!dalamJam(sesi)) {
    const nama = sesi === 'pagi' ? 'Presensi Pagi' : 'Presensi Sore';
    $('popupJudul').textContent = nama + ' belum tersedia';
    $('popupPesan').textContent = nama + ' hanya dapat dilakukan pukul ' + formatJam(JENDELA[sesi].mulai) + ' - ' + formatJam(JENDELA[sesi].selesai) + ' WIB.';
    $('popupBelumWaktunya').style.display = 'flex';
    perbaruiSesiAman();
    return;
  }

  sesiAktif = sesi;
  $('vPilihSesi').style.display = 'none';
  $('vScan').style.display = 'block';
  $('scanStatus').textContent = 'Meminta izin lokasi...';

  const lanjut = () => { mulaiTracking(); mulaiScan(); };
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(lanjut, lanjut, { enableHighAccuracy: true, timeout: 8000 });
  } else {
    lanjut();
  }
}

function tutupPopupWaktu() { $('popupBelumWaktunya').style.display = 'none'; }

/* ===== GPS ===== */
const SMOOTH = 0.4;
let userLat = null, userLng = null, akurasi = null, bufer = null, watchId = null, tileTimer = null;

function jarak(a, b, c, d) {
  const R = 6371e3, x = (c - a) * Math.PI / 180, y = (d - b) * Math.PI / 180;
  const h = Math.sin(x / 2) ** 2 + Math.cos(a * Math.PI / 180) * Math.cos(c * Math.PI / 180) * Math.sin(y / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
}
const cm = m => `${(m * 100).toFixed(0)} cm`;

function posTerdekat() {
  if (userLat === null) return null;
  let t = null;
  for (const p of POSISI) {
    const j = jarak(p.lat, p.lng, userLat, userLng);
    if (!t || j < t.jarak) t = { pos: p, jarak: j };
  }
  return t;
}

function mulaiTracking() {
  if (watchId !== null || !navigator.geolocation) return;
  watchId = navigator.geolocation.watchPosition(pos => {
    const now = Date.now(), a = pos.coords.accuracy;
    if (!bufer || now - bufer.t > 10000 || a < bufer.a) bufer = { a, t: now };
    akurasi = bufer.a;
    if (userLat === null) { userLat = pos.coords.latitude; userLng = pos.coords.longitude; }
    else { userLat += SMOOTH * (pos.coords.latitude - userLat); userLng += SMOOTH * (pos.coords.longitude - userLng); }
  }, e => console.warn('GPS:', e.message), { enableHighAccuracy: true, timeout: 3000, maximumAge: 700 });
}

function perbaruiTile() {
  const t = posTerdekat();
  if (t) {
    $('tJarakL').textContent = 'Jarak ke ' + t.pos.nama;
    $('tJarak').textContent = cm(t.jarak);
    $('tJarak').className = t.jarak <= t.pos.radius ? 'baik' : '';
  }
  if (akurasi !== null) {
    const ok = akurasi <= MAX_AKURASI;
    $('tAkurasi').textContent = akurasi.toFixed(1) + ' m' + (ok ? '' : ' (Kurang)');
    $('tAkurasi').className = ok ? 'baik' : 'buruk';
  }
}

/* ===== SCAN QR ===== */
const BATAS_TUNGGU_MS = 6000;
let streamScan = null, scanning = false, memproses = false, target = null, ident = null;

function notif(jenis, teks) { $('notif').className = 'notif ' + jenis; $('notif').textContent = teks; }
function bracket(ok) { ['b1', 'b2', 'b3', 'b4'].forEach(i => $(i).classList.toggle('ok', ok)); }

function mulaiScan() {
  scanning = true; memproses = false; bracket(false);
  $('notif').className = 'notif';
  clearInterval(tileTimer);
  tileTimer = setInterval(perbaruiTile, 500);

  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
    .then(s => {
      streamScan = s;
      $('videoScan').srcObject = s;
      $('videoScan').play();
      $('scanStatus').textContent = 'Arahkan Kartu ID ke kamera...';
      requestAnimationFrame(frame);
    })
    .catch(() => { $('scanStatus').textContent = 'Kamera tidak bisa diakses. Aktifkan izin kamera.'; });
}

function stopStreamScan() {
  if (streamScan) { streamScan.getTracks().forEach(t => t.stop()); streamScan = null; }
}

function frame() {
  if (!scanning) return;
  const v = $('videoScan');

  if (v.readyState === v.HAVE_ENOUGH_DATA && !memproses) {
    const c = $('canvasScan');
    c.width = v.videoWidth; c.height = v.videoHeight;
    const x = c.getContext('2d', { willReadFrequently: true });
    x.drawImage(v, 0, 0, c.width, c.height);
    const d = x.getImageData(0, 0, c.width, c.height);
    const k = jsQR(d.data, d.width, d.height, { inversionAttempts: 'dontInvert' });
    if (k && k.data) { memproses = true; prosesQR(String(k.data).trim()); }
  }
  requestAnimationFrame(frame);
}

function ulangScan(pesan, ms) {
  notif('gagal', pesan);
  setTimeout(() => {
    $('notif').className = 'notif';
    memproses = false;
    $('scanStatus').textContent = 'Arahkan Kartu ID ke kamera...';
  }, ms);
}

function prosesQR(uuid) {
  if (!uuid) return ulangScan('Kartu ID tidak terbaca dengan benar.', 2200);

  notif('proses', 'Kartu ID terbaca. Memastikan identitas & lokasi Anda...');
  $('scanStatus').textContent = 'Memvalidasi...';

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

  fetch(ROUTE_VERIFIKASI, { method: 'POST', headers: headerJson(), body: JSON.stringify({ uuid }) })
    .then(r => r.json())
    .then(d => {
      if (!d.valid) return ulangScan(d.pesan || 'Kartu ID tidak dikenali.', 2600);

      ident = { uuid, nama: d.nama };
      target = t.pos;
      scanning = false;
      bracket(true);
      notif('berhasil', `Absen berhasil, ${d.nama.toUpperCase()}! Lokasi: ${t.pos.nama}.`);

      setTimeout(() => {
        stopStreamScan();
        clearInterval(tileTimer);
        sesiAktif === 'pagi' ? bukaSelfie() : kirimSoreLalu();
      }, 1400);
    })
    .catch(() => ulangScan('Tidak bisa menghubungi server untuk verifikasi.', 2600));
}

function ulangDariScan() {
  tutupKamera();
  $('vSelfie').style.display = 'none';
  $('vScan').style.display = 'block';
  ident = null; target = null;
  mulaiScan();
}

/* ===== KIRIM PRESENSI KE SERVER =====
   Kalau server membalas error tanpa field "pesan" (mis. 419/413/429/500),
   tampilkan penyebab + kode statusnya, bukan sekadar "tidak diketahui". */
function kirimSimpan(payload) {
  return fetch(ROUTE_SIMPAN, { method: 'POST', headers: headerJson(), body: JSON.stringify(payload) })
    .then(async r => {
      let d = null;
      try { d = await r.json(); } catch (e) { d = null; }
      if (!d) d = { ok: false };
      if (!d.ok && !d.pesan) {
        const alasan = {
          401: 'Sesi login habis. Silakan login ulang.',
          403: 'Akses ditolak.',
          413: 'Ukuran foto terlalu besar untuk server.',
          419: 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu coba lagi.',
          429: 'Terlalu banyak percobaan. Tunggu 1 menit lalu coba lagi.',
          500: 'Terjadi kesalahan di server.',
        };
        d.pesan = (alasan[r.status] || d.message || 'Respons server tidak dikenali') + ' (kode ' + r.status + ')';
      }
      return d;
    });
}

/* ===== SESI SORE ===== */
function kirimSoreLalu() {
  kirimSimpan({ uuid: ident.uuid, sesi: 'sore', lat: userLat, lng: userLng, akurasi, deviceId })
    .then(d => {
      if (!d.ok) {
        alert('Gagal: ' + (d.pesan || 'tidak diketahui'));
        if (d.laporanUrl) { window.location.href = d.laporanUrl; return; }
        return ulangDariScan();
      }
      window.location.href = ROUTE_LAPORAN.replace('__ID__', d.sesiPresensiId);
    })
    .catch(() => { alert('Tidak bisa terhubung ke server.'); ulangDariScan(); });
}

/* ===== SESI PAGI: SELFIE ===== */
let streamCam = null, foto = null, tahap = 'idle';

function bukaSelfie() {
  $('vScan').style.display = 'none';
  $('vSelfie').style.display = 'block';
  tahap = 'idle'; foto = null;
  $('video').style.display = 'none';
  $('preview').style.display = 'none';
  $('ph').style.display = 'block';
  $('btnSelfie').textContent = 'Aktifkan Kamera';
  $('btnSelfie').disabled = false;
  $('btnUlang').style.display = 'none';
  $('errSelfie').textContent = '';
}

function tutupKamera() {
  if (streamCam) { streamCam.getTracks().forEach(t => t.stop()); streamCam = null; }
}

function masihDiZona() {
  const j = jarak(target.lat, target.lng, userLat, userLng);
  if (j > target.radius) {
    $('errSelfie').textContent = `Kamu sudah keluar dari zona absen (jarak ${cm(j)}). Ulangi dari scan QR.`;
    return false;
  }
  return true;
}

function aksiSelfie() {
  $('errSelfie').textContent = '';

  if (tahap === 'idle') {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
      .then(s => {
        streamCam = s;
        $('video').srcObject = s;
        $('video').play();
        $('video').style.display = 'block';
        $('ph').style.display = 'none';
        tahap = 'live';
        $('btnSelfie').textContent = '📸 Ambil Foto';
      })
      .catch(() => { $('errSelfie').textContent = 'Kamera tidak bisa diakses. Pastikan izin kamera diaktifkan.'; });

  } else if (tahap === 'live') {
    if (!masihDiZona()) return;
    const v = $('video'), c = $('canvasFoto');
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0, c.width, c.height);
    foto = c.toDataURL('image/jpeg', 0.9);
    $('preview').src = foto;
    $('preview').style.display = 'block';
    $('video').style.display = 'none';
    tutupKamera();
    tahap = 'preview';
    $('btnSelfie').textContent = '✓ Gunakan Foto & Kirim Presensi';
    $('btnUlang').style.display = 'block';

  } else if (tahap === 'preview') {
    if (!masihDiZona()) return;
    $('btnSelfie').disabled = true;
    $('btnSelfie').textContent = 'Mengirim...';

    kirimSimpan({ uuid: ident.uuid, sesi: 'pagi', lat: userLat, lng: userLng, akurasi, deviceId, fotoBase64: foto })
      .then(d => {
        $('btnSelfie').disabled = false;
        $('btnSelfie').textContent = '✓ Gunakan Foto & Kirim Presensi';

        if (!d.ok) { $('errSelfie').textContent = 'Gagal: ' + (d.pesan || 'tidak diketahui'); return; }

        const w = waktuWIB();
        $('okNama').textContent = d.nama;
        $('okWaktu').textContent = `Tercatat ${w.getUTCDate()} ${BULAN_NAMA[w.getUTCMonth()]} ${w.getUTCFullYear()}, ${p2(w.getUTCHours())}:${p2(w.getUTCMinutes())} WIB`;
        $('okAv').innerHTML = d.fotoUrl ? `<img src="${d.fotoUrl}" alt="">` : 'FOTO';
        $('mOk').style.display = 'flex';
      })
      .catch(() => {
        $('btnSelfie').disabled = false;
        $('btnSelfie').textContent = '✓ Gunakan Foto & Kirim Presensi';
        $('errSelfie').textContent = 'Tidak bisa terhubung ke server.';
      });
  }
}

function ulangFoto() { tutupKamera(); bukaSelfie(); }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Maganghub\webpresensi\resources\views/presensi/scan.blade.php ENDPATH**/ ?>