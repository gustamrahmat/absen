@extends('layouts.app')
@section('judul', 'Presensi Keluar')

@push('css')
<style>
.head{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.back{width:34px;height:34px;border-radius:50%;border:1px solid var(--bd);background:#fff;cursor:pointer;font-size:16px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;color:var(--nv)}
.row{display:flex;justify-content:space-between;align-items:center;background:#f4f6fb;border-radius:12px;padding:10px 14px;margin-bottom:14px}
.row small{display:block;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase}.row b{font-size:17px}
.tag{font-size:11px;font-weight:700;color:#0f7a3a;background:#e7faf1;border-radius:20px;padding:5px 10px}
label{display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:8px}
label em{font-style:normal;color:var(--mr);font-size:11.5px}
label.opsional em{color:var(--gy)}
textarea{width:100%;border:1.5px solid var(--bd);border-radius:14px;padding:12px;font:14px Inter,sans-serif;resize:vertical;margin-bottom:4px}
textarea:focus{outline:0;border-color:var(--b1)}
#taLaporan{min-height:200px}
.cnt{display:flex;justify-content:space-between;font-size:12px;margin:4px 0 16px;color:var(--mr)}.cnt.ok{color:var(--b1)}
.warn{font-size:12px;background:#fef3e2;color:#9a5b0a;border-radius:12px;padding:10px 12px;margin-bottom:14px;line-height:1.5}
.err{color:var(--mr);font-size:12.5px;margin-top:10px}
.cek{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#34d399,#16a34a);color:#fff;font-size:34px;line-height:64px;margin:0 auto 14px;text-align:center}
.pro{display:flex;align-items:center;gap:12px;background:#f4f6fb;border-radius:14px;padding:12px;margin:16px 0;text-align:left}
.pro .av{width:44px;height:44px;border-radius:10px;background:#dde1ee;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:10px;color:#94a3b8;flex-shrink:0}
.pro .av img{width:100%;height:100%;object-fit:cover}
.pro b{display:block;font-size:14px}.pro small{font-size:11.5px;color:var(--gy)}
</style>
@endpush

@section('konten')
<div class="head"><a href="{{ route('presensi.index') }}" class="back">←</a><h1>Presensi Keluar</h1></div>

<div class="card">
  <div class="row">
    <div><small>Jam keluar</small><b>{{ $sesiPresensi->waktu_absen?->format('H:i:s') ?? '-' }} WIB</b></div>
    <span class="tag">● QR Terverifikasi</span>
  </div>

  <label>Laporan Harian <em>* Wajib (Min. 100 karakter)</em></label>
  <textarea id="taLaporan" maxlength="1000" placeholder="Ceritakan kegiatan yang kamu kerjakan hari ini, dan kendala yang kamu hadapi (kalau ada)" oninput="cekForm()"></textarea>
  <div class="cnt" id="cntLaporan"><span id="cntMsg">Belum memenuhi syarat min. 100 karakter</span><span id="cntNum">0 / 500</span></div>

  <div class="warn">Laporan kegiatan harian ini akan diverifikasi oleh <b>Mentor Divisi</b> sebagai syarat persetujuan jam kehadiran harian.</div>
  <button class="btn" id="btnKirim" type="button" onclick="kirim()" disabled>➤ Kirim Presensi Pulang</button>
  <div class="err" id="errForm"></div>
</div>

<div id="mOk" style="position:fixed;inset:0;background:rgba(20,25,50,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:50">
  <div style="background:#fff;border-radius:22px;padding:24px;width:100%;max-width:360px;text-align:center">
    <div class="cek">✓</div><h2 style="margin:0 0 6px;font-size:17px">Presensi Berhasil Tercatat</h2>
    <p class="info" style="margin:0;font-size:12.5px;color:var(--gy)">Kehadiranmu hari ini sudah berhasil dicatat dan bisa dilihat kapan saja di halaman presensi.</p>
    <div class="pro">
      <div class="av" id="okAv">
        @if (auth()->user()->foto_profil_url)
          <img src="{{ auth()->user()->foto_profil_url }}" alt="">
        @else
          FOTO
        @endif
      </div>
      <div><b>{{ auth()->user()->name }}</b><small id="okWaktu">-</small></div>
    </div>
    <a href="{{ route('presensi.index') }}" class="btn">Selesai</a>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = id => document.getElementById(id);
const p2 = n => String(n).padStart(2, '0');
const BULAN_NAMA = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function cekForm() {
  const n = $('taLaporan').value.trim().length, ok = n >= 100;
  $('cntLaporan').classList.toggle('ok', ok);
  $('cntMsg').textContent = ok ? '✓ Memenuhi syarat min. 100 karakter' : 'Belum memenuhi syarat min. 100 karakter';
  $('cntNum').textContent = n + ' / 1000';
  $('btnKirim').disabled = !ok;
}

function kirim() {
  $('errForm').textContent = '';
  $('btnKirim').disabled = true; $('btnKirim').textContent = 'Mengirim...';

  fetch('{{ route('laporan.store', $sesiPresensi) }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ laporan: $('taLaporan').value.trim() })
  }).then(r => r.json()).then(d => {
    if (!d.ok) {
      $('btnKirim').disabled = false; $('btnKirim').textContent = '➤ Kirim Presensi Pulang';
      $('errForm').textContent = 'Gagal: ' + (d.pesan || 'tidak diketahui');
      return;
    }
    const w = new Date();
    $('okWaktu').textContent = `Tercatat ${w.getDate()} ${BULAN_NAMA[w.getMonth()]} ${w.getFullYear()}, ${p2(w.getHours())}:${p2(w.getMinutes())} WIB`;
    $('mOk').style.display = 'flex';
  }).catch(() => {
    $('btnKirim').disabled = false; $('btnKirim').textContent = '➤ Kirim Presensi Pulang';
    $('errForm').textContent = 'Tidak bisa terhubung ke server.';
  });
}
</script>
@endsection
