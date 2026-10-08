<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak Kartu ID Prakerin</title>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<style>
:root{--b1:#3b5bf0;--nv:#0f1420;--gy:#64748b;--bd:#e3e6ef}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,-apple-system,Segoe UI,Arial,sans-serif;color:var(--nv);background:#f3f4f6}
.bar{max-width:420px;margin:20px auto;background:#fff;border-radius:16px;padding:18px;box-shadow:0 8px 20px -12px rgba(30,41,89,.25)}
.bar h1{font-size:18px;margin:0 0 4px}.bar p{font-size:12.5px;color:var(--gy);line-height:1.5;margin:0 0 12px}
.bar select,.bar button{width:100%;padding:11px;border-radius:10px;font:14px inherit;margin-bottom:8px}
.bar select{border:1.5px solid var(--bd)}
.bar button{border:0;background:linear-gradient(100deg,#3b5bf0,#8b5cf6);color:#fff;font-weight:700;cursor:pointer}
.bar .sec{background:#f1f3f9;color:var(--nv)}
.kosong{text-align:center;color:var(--gy);font-size:13px;padding:30px}

/* Satu "halaman" = maks. 8 kartu (2 kolom x 4 baris), ukuran kartu 85,6 x 54 mm (ID card standar) */
.halaman{width:182mm;margin:0 auto 12px;padding:4mm 0;break-after:page;page-break-after:always}
.halaman:last-child{break-after:auto;page-break-after:auto}
.judul-halaman{font-size:9pt;color:#555;margin:0 0 3mm}
.grid{display:grid;grid-template-columns:repeat(2,85.6mm);gap:2mm 4mm}
.kartu{width:85.6mm;height:54mm;border:.25mm dashed #999;border-radius:3mm;padding:4mm;display:flex;gap:3mm;background:#fff;break-inside:avoid;page-break-inside:avoid;overflow:hidden}
.info{flex:1;min-width:0;display:flex;flex-direction:column}
.merek{font-size:8pt;font-weight:800;color:var(--b1);line-height:1.2}.merek small{display:block;font-size:6pt;color:#e11d2e;font-weight:700}
.nama{font-size:11pt;font-weight:800;margin:3mm 0 1mm;line-height:1.2;word-break:break-word}
.baris{font-size:7.5pt;color:#334155;line-height:1.5;word-break:break-word}
.divisi{margin-top:auto;font-size:8pt;font-weight:700;background:#eef2ff;color:var(--b1);border-radius:99px;padding:1mm 3mm;align-self:flex-start}
.qr{width:30mm;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.qr img,.qr canvas{width:30mm!important;height:30mm!important}

@page{size:A4;margin:8mm}
@media print{
  body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .bar{display:none}
  .halaman{margin:0;padding:0}
}
</style>
</head>
<body>
<div class="bar">
  <h1>Cetak Kartu ID</h1>
  <p>Pilih divisi, lalu klik cetak dan pilih <b>Simpan sebagai PDF</b> (kertas A4, skala 100%, nyalakan "Grafik latar belakang"). Pilih satu divisi per PDF, atau "Semua divisi" untuk satu PDF yang berganti halaman tiap divisi.</p>
  <form method="GET" action="{{ route('kartu.cetak') }}">
    <select name="divisi" onchange="this.form.submit()">
      <option value="">Semua divisi</option>
      @foreach ($daftarDepartemen as $dep)
        <option value="{{ $dep->id }}" @selected($pilihan === (string) $dep->id)>{{ $dep->nama }}</option>
      @endforeach
      <option value="tanpa" @selected($pilihan === 'tanpa')>Tanpa divisi</option>
    </select>
  </form>
  <button type="button" onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>
  <button type="button" class="sec" onclick="history.back()">← Kembali</button>
</div>

@forelse ($kelompok as $namaDivisi => $daftarPeserta)
  @foreach ($daftarPeserta->chunk(8) as $nomorHalaman => $isiHalaman)
    <section class="halaman">
      <p class="judul-halaman">Divisi: <b>{{ $namaDivisi }}</b> · {{ $daftarPeserta->count() }} kartu · hal. {{ $nomorHalaman + 1 }}/{{ ceil($daftarPeserta->count() / 8) }}</p>
      <div class="grid">
        @foreach ($isiHalaman as $p)
          <div class="kartu">
            <div class="info">
              <div class="merek">NEW ARMADA<small>PT MEKAR ARMADA JAYA</small></div>
              <div class="nama">{{ $p->name }}</div>
              <div class="baris">NIM/NISN: <b>{{ $p->nomor_induk }}</b></div>
              @if ($p->asal_sekolah)<div class="baris">{{ $p->asal_sekolah }}</div>@endif
              <div class="divisi">{{ $namaDivisi }}</div>
            </div>
            <div class="qr" data-uuid="{{ $p->uuid_kartu }}"></div>
          </div>
        @endforeach
      </div>
    </section>
  @endforeach
@empty
  <div class="kosong">Belum ada peserta dengan Kartu ID untuk pilihan ini.<br>Pastikan akun peserta sudah ditautkan ke data magang.</div>
@endforelse

<script>
// QR HANYA berisi UUID polos -- bukan JSON, bukan data pribadi.
document.querySelectorAll('[data-uuid]').forEach(function (el) {
  new QRCode(el, { text: el.dataset.uuid, width: 256, height: 256, correctLevel: QRCode.CorrectLevel.M });
});
</script>
</body>
</html>
