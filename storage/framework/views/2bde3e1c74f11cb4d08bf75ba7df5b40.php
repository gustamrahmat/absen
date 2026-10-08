<?php $__env->startSection('judul', 'Presensi Magang'); ?>

<?php $__env->startPush('css'); ?>
<style>
.cal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.cal-head b{font-size:14.5px}
.nav-bulan a{display:inline-block;width:26px;height:26px;line-height:26px;text-align:center;border:1px solid var(--bd);border-radius:8px;text-decoration:none;color:var(--nv);margin-left:6px}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center}
.cal-dow{font-size:10.5px;color:#9aa1b4;font-weight:700;margin-bottom:4px}
.cal-day{position:relative;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;font-size:12.5px;border-radius:8px;background:#f4f6fb;color:var(--nv)}
.cal-day i{position:absolute;bottom:2px;font-style:normal;font-size:9px;font-weight:800}
.cal-day.st-hadir{background:#e7faf1}.cal-day.st-hadir i{color:var(--hj)}
.cal-day.st-izin{background:#fef3e2}.cal-day.st-izin i{color:var(--kn)}
.cal-day.st-sakit{background:#eaf1ff}.cal-day.st-sakit i{color:var(--bi)}
.cal-day.st-cuti{background:#f3e8ff}.cal-day.st-cuti i{color:#9333ea}
.cal-day.st-alpha{background:#fef2f2;color:var(--mr)}.cal-day.st-alpha i{color:var(--mr)}
.cal-day.st-perlu_acc{background:#fff7ed}.cal-day.st-perlu_acc i{color:#ea580c;font-size:12px}
.cal-day.st-progres i{color:#94a3b8;font-size:12px}
.legenda{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px;font-size:10.5px;color:var(--gy)}
.legenda .dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px}
.legenda .hadir{background:var(--hj)}.legenda .izin{background:var(--kn)}.legenda .sakit{background:var(--bi)}.legenda .alpha{background:var(--mr)}.legenda .kosong{background:#cbd5e1}
.tiles{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.tile{background:#fff;border:1px solid var(--bd);border-radius:14px;padding:12px;text-align:center}
.tile b{display:block;font-size:20px}.tile small{font-size:11px;color:var(--gy)}
.tile b.hijau{color:var(--hj)}.tile b.kuning{color:var(--kn)}.tile b.biru{color:var(--bi)}.tile b.merah{color:var(--mr)}

.cal-day.st-libur{background:transparent;color:#cbd5e1}
.legenda .progres{background:#94a3b8}.legenda .perlu{background:#ea580c}
.banner{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:14px;padding:12px 14px;margin-bottom:14px;font-size:12.5px;line-height:1.5}
.banner a{font-weight:700;color:#9a3412}
.banner.gagal{background:#fef2f2;border-color:#fecaca;color:var(--mr)}
.tugas-item{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f3f9}
.tugas-item:last-child{border-bottom:0}
.tugas-item b{display:block;font-size:13px;word-break:break-all}.tugas-item small{font-size:11px;color:var(--gy)}
.tugas-item a{font-size:12px;font-weight:700;color:var(--b1);text-decoration:none;white-space:nowrap}
.modal{position:fixed;inset:0;background:rgba(20,25,50,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
.modal.buka{display:flex}
.modal .box{background:#fff;border-radius:20px;padding:22px;width:100%;max-width:380px}
.modal h2{margin:0 0 6px;font-size:17px}
.modal .info{font-size:12.5px;color:var(--gy);line-height:1.5;margin:0 0 12px}
.modal input[type=file]{width:100%;font-size:13px;margin-bottom:6px}
.modal .err{color:var(--mr);font-size:12.5px;min-height:18px;margin-bottom:8px}
.btn.abu{background:#e5e7eb;color:var(--nv)}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('konten'); ?>
<h1>Presensi Magang</h1>
<p class="sub">Rekap kehadiranmu bulan ini.</p>

<?php if(session('gagal_tugas')): ?><div class="banner gagal"><?php echo e(session('gagal_tugas')); ?></div><?php endif; ?>

<?php $__currentLoopData = $laporanTertunda; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tertunda): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="banner">
    ⚠️ Laporan harian tanggal <b><?php echo e($tertunda->tanggal->translatedFormat('d F Y')); ?></b> belum diisi.
    <a href="<?php echo e(route('laporan.form', $tertunda)); ?>">Isi sekarang</a>
  </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<div class="card">
  <div class="cal-head">
    <b><?php echo e($bulan->translatedFormat('F Y')); ?></b>
    <div class="nav-bulan">
      <a href="<?php echo e(route('presensi.index', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')])); ?>">‹</a>
      <a href="<?php echo e(route('presensi.index', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')])); ?>">›</a>
    </div>
  </div>

  <div class="cal-grid cal-dow"><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span><span>M</span></div>
  <div class="cal-grid">
    <?php for($i = 0; $i < $bulan->copy()->startOfMonth()->dayOfWeekIso - 1; $i++): ?>
      <span></span>
    <?php endfor; ?>
    <?php $__currentLoopData = $hariDalamBulan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tgl => $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <span class="cal-day st-<?php echo e($info['kode']); ?>" title="<?php echo e(\Illuminate\Support\Carbon::parse($tgl)->translatedFormat('d F Y')); ?>">
        <?php echo e(\Illuminate\Support\Carbon::parse($tgl)->day); ?>

        <i><?php echo e($info['tanda']); ?></i>
      </span>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  <div class="legenda">
    <span><i class="dot hadir"></i>Hadir</span>
    <span><i class="dot izin"></i>Izin</span>
    <span><i class="dot sakit"></i>Sakit</span>
    <span><i class="dot alpha"></i>Alpha</span>
    <span><i class="dot progres"></i>Baru 1 sesi (…)</span>
    <span><i class="dot perlu"></i>Menunggu persetujuan HRD (!)</span>
  </div>
</div>

<div class="tiles">
  <div class="tile"><b class="hijau"><?php echo e($hitung['hadir']); ?></b><small>Hari Hadir</small></div>
  <div class="tile"><b class="kuning"><?php echo e($hitung['izin']); ?></b><small>Hari Izin</small></div>
  <div class="tile"><b class="biru"><?php echo e($hitung['sakit']); ?></b><small>Hari Sakit</small></div>
  <div class="tile"><b class="merah"><?php echo e($hitung['alpha']); ?></b><small>Hari Alpha</small></div>
</div>

<a href="<?php echo e(route('presensi.scan')); ?>" class="btn">📷 Presensi Scan<small>Scan QR ID card untuk absen</small></a>
<button type="button" class="btn-outline" onclick="bukaUpload()">📎 Upload Tugas<small>Kirim tugas dari mentor (jpg, png, pdf, doc, docx; maks 2 MB)</small></button>
<a href="<?php echo e(route('laporan.riwayat')); ?>" class="btn-outline">📝 Laporan Harian<small>Lihat riwayat laporan harianmu</small></a>

<div class="card" style="margin-top:16px">
  <b style="font-size:14px">Riwayat Tugas</b>
  <?php $__empty_1 = true; $__currentLoopData = $riwayatTugas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="tugas-item">
      <div><b><?php echo e($t->nama_file_asli); ?></b><small><?php echo e($t->created_at->translatedFormat('d F Y, H:i')); ?> · <?php echo e($t->ukuran_kb); ?> KB</small></div>
      <a href="<?php echo e(route('tugas.unduh', $t)); ?>">Buka</a>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p style="font-size:12.5px;color:var(--gy);margin:10px 0 0">Belum ada tugas diunggah.</p>
  <?php endif; ?>
</div>

<div class="modal" id="mUpload" onclick="if(event.target===this)tutupUpload()">
  <div class="box">
    <h2>Upload Tugas</h2>
    <p class="info">Jenis file: jpg, png, pdf, doc, docx. Ukuran maksimal 2 MB.</p>
    <form method="POST" action="<?php echo e(route('tugas.simpan')); ?>" enctype="multipart/form-data" onsubmit="return cekSebelumKirim()">
      <?php echo csrf_field(); ?>
      <input type="file" name="file" id="fileTugas" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="cekFileTugas()">
      <div class="err" id="errUpload"></div>
      <button class="btn" id="btnKirimTugas" type="submit" disabled>Unggah Tugas</button>
      <button class="btn abu" type="button" onclick="tutupUpload()" style="margin-top:8px">Batal</button>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
const MAKS_UKURAN_TUGAS = 2 * 1024 * 1024;
const EKSTENSI_BOLEH = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
const elFile = () => document.getElementById('fileTugas');

function bukaUpload() {
  elFile().value = ''; document.getElementById('errUpload').textContent = '';
  document.getElementById('btnKirimTugas').disabled = true;
  document.getElementById('mUpload').classList.add('buka');
}
function tutupUpload() { document.getElementById('mUpload').classList.remove('buka'); }
// Pengecekan di sini hanya untuk kenyamanan -- pemeriksaan sebenarnya dilakukan di server.
function cekFileTugas() {
  const f = elFile().files[0], err = document.getElementById('errUpload'), btn = document.getElementById('btnKirimTugas');
  err.textContent = ''; btn.disabled = true;
  if (!f) return;
  const ekstensi = f.name.split('.').pop().toLowerCase();
  if (!EKSTENSI_BOLEH.includes(ekstensi)) { err.textContent = 'Jenis file tidak didukung. Gunakan jpg, png, pdf, doc, atau docx.'; return; }
  if (f.size > MAKS_UKURAN_TUGAS) { err.textContent = `File terlalu besar (${(f.size / 1024 / 1024).toFixed(2)} MB). Maksimal 2 MB.`; return; }
  btn.disabled = false;
}
function cekSebelumKirim() {
  const btn = document.getElementById('btnKirimTugas');
  btn.disabled = true; btn.textContent = 'Mengunggah...';
  return true;
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Maganghub\webpresensi\resources\views/presensi/index.blade.php ENDPATH**/ ?>