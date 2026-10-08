@extends('layouts.admin')
@section('content')
@php
  $badge = [
    'hadir' => 'bg-green-100 text-green-700', 'izin' => 'bg-amber-100 text-amber-700',
    'sakit' => 'bg-blue-100 text-blue-700',   'cuti' => 'bg-teal-100 text-teal-700',
    'alpha' => 'bg-red-100 text-red-600',
  ];
  $inp = 'border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-200';

  // Warna persentase kehadiran: >=85 hijau, 70-84 kuning, <70 merah
  $warnaTeks = fn($p) => $p === null ? 'text-slate-400' : ($p >= 85 ? 'text-green-600' : ($p >= 70 ? 'text-amber-600' : 'text-red-600'));
  $warnaBar  = fn($p) => $p >= 85 ? 'bg-green-500' : ($p >= 70 ? 'bg-amber-400' : 'bg-red-500');
  $seg = [
    ['hadir', 'Hadir', 'bg-green-500'], ['izin', 'Izin', 'bg-amber-400'], ['sakit', 'Sakit', 'bg-blue-500'],
    ['cuti', 'Cuti', 'bg-teal-400'],    ['alpha', 'Alpha', 'bg-red-500'], ['tanpa_data', 'Tanpa data', 'bg-slate-300'],
  ];
  $tot = $rekap['total'];
@endphp

<div x-data="presensi()" x-init="@if($qrBaru) open('qr', @js($qrBaru)) @endif" @keydown.escape.window="modal = null">

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold">Presensi Peserta Magang</h1>
      <p class="text-xs text-slate-500 mt-1">Pantau kehadiran harian dan koreksi status jika ada peserta izin, sakit, atau cuti.</p>
    </div>
    <div class="flex gap-2 shrink-0">
      <a href="{{ route('admin.peserta.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg px-4 py-2.5">+ Tambah Anak Magang</a>
      <button type="button" @click="modal = 'import'" class="border border-slate-300 hover:bg-slate-50 text-xs font-semibold rounded-lg px-4 py-2.5">⇧ Import Excel</button>
      <button type="button" @click="modal = 'export'" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg px-4 py-2.5">⇩ Export Excel</button>
      <button type="button" @click="modal = 'accAll'" @disabled($stat['acc'] === 0) class="bg-gradient-to-r from-purple-600 to-violet-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg px-4 py-2.5">✓ ACC Semua ({{ $stat['acc'] }})</button>
    </div>
  </div>

  {{-- Pesan sukses / error --}}
  @if(session('success'))
    <div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-xs px-4 py-3">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3">{{ $errors->first() }}</div>
  @endif

  @if(!empty(session('import_errors')))
    <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs px-4 py-3">
      <b>Catatan import:</b>
      <ul class="list-disc pl-4 mt-1 max-h-32 overflow-y-auto">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- Statistik --}}
  <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-8">
    @foreach([['Hadir Hari Ini',$stat['hadir'],'text-green-600'],['Izin',$stat['izin'],'text-amber-600'],['Sakit',$stat['sakit'],'text-blue-600'],['Alpha',$stat['alpha'],'text-red-600'],['Butuh ACC',$stat['acc'],'text-purple-600']] as [$l,$v,$c])
      <div class="border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
        <div class="text-2xl font-bold {{ $c }}">{{ $v }}</div>
        <div class="text-[11px] text-slate-500">{{ $l }}</div>
      </div>
    @endforeach
  </div>

  {{-- Filter --}}
  <form method="GET" class="flex flex-wrap items-center gap-2 mt-4">
    <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden text-xs">
      <a href="{{ request()->fullUrlWithQuery(['tanggal' => $prev]) }}" class="px-3 py-2 hover:bg-slate-50">‹</a>
      <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" onchange="this.form.submit()" class="py-2 text-xs focus:outline-none">
      <a href="{{ request()->fullUrlWithQuery(['tanggal' => $next]) }}" class="px-3 py-2 hover:bg-slate-50">›</a>
    </div>
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama peserta..." class="{{ $inp }} flex-1 min-w-[200px]">
    <select name="status" onchange="this.form.submit()" class="{{ $inp }}">
      <option value="">Semua Status</option>
      @foreach(['hadir','izin','sakit','cuti','alpha'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
      <option value="kosong" @selected(request('status')==='kosong')>Belum ada status</option>
    </select>
    <select name="departemen" onchange="this.form.submit()" class="{{ $inp }}">
      <option value="">Semua Departemen</option>
      @foreach($departemen as $d)<option @selected(request('departemen')===$d)>{{ $d }}</option>@endforeach
    </select>
  </form>

  {{-- Ringkasan kehadiran bulanan (mengikuti filter nama & departemen) --}}
  <div class="mt-4 border border-slate-200 rounded-xl px-5 py-4 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Kehadiran {{ $rekap['label'] }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">
          {{ request('departemen') ?: 'Semua Departemen' }}
          @if(request('q')) · pencarian "{{ request('q') }}" @endif
          · {{ $rekap['jumlah_peserta'] }} peserta
          @if($rekap['sampai_label']) · {{ $rekap['hari_kerja'] }} hari kerja sampai {{ $rekap['sampai_label'] }} @endif
        </div>
      </div>
      <div class="text-3xl font-extrabold leading-none {{ $warnaTeks($tot['persen']) }}">{{ $tot['persen'] === null ? '—' : $tot['persen'] . '%' }}</div>
    </div>

    <div class="flex h-2.5 rounded-full overflow-hidden bg-slate-100 mt-3">
      @if($tot['hari_kerja'] > 0)
        @foreach($seg as [$k, $lbl, $warna])
          @if($tot[$k] > 0)
            <div class="{{ $warna }}" @style(['width: ' . round($tot[$k] / $tot['hari_kerja'] * 100, 2) . '%']) title="{{ $lbl }}: {{ $tot[$k] }} hari"></div>
          @endif
        @endforeach
      @endif
    </div>

    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-[11px] text-slate-500">
      @foreach($seg as [$k, $lbl, $warna])
        <span class="inline-flex items-center gap-1.5"><i class="inline-block w-2 h-2 rounded-full {{ $warna }}"></i>{{ $lbl }} <b class="text-slate-700">{{ $tot[$k] }}</b></span>
      @endforeach
    </div>
    <p class="text-[10px] text-slate-400 mt-2">Persentase = hari hadir ÷ hari kerja (Senin–Jumat), dihitung sejak tanggal mulai magang peserta. Angka di legenda dalam satuan hari.</p>
  </div>

  {{-- Tabel --}}
  <div class="mt-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm overflow-x-auto">
    <table class="w-full text-xs">
      <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
        <tr>
          @foreach(['Peserta','Departemen','Jam Masuk','Jam Keluar','Catatan Harian','Status','Kehadiran Bulan Ini','Aksi'] as $h)
            <th class="text-left font-semibold px-4 py-3">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($rows as $row)
          <tr class="hover:bg-slate-50/60">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                @if($row['foto'])
                  <img src="{{ $row['foto'] }}" class="w-8 h-8 rounded-full object-cover" alt="">
                @else
                  <div class="w-8 h-8 rounded-full bg-slate-200 text-[9px] font-bold text-slate-500 flex items-center justify-center">{{ $row['inisial'] }}</div>
                @endif
                <span class="font-semibold">{{ $row['nama'] }}</span>
              </div>
            </td>
            <td class="px-4 py-3">{{ $row['departemen'] }}</td>
            <td class="px-4 py-3 {{ $row['masuk'] ? '' : 'text-slate-400' }}">{{ $row['masuk'] ?? 'Belum absen' }}</td>
            <td class="px-4 py-3 {{ $row['keluar'] ? '' : 'text-slate-400' }}">{{ $row['keluar'] ?? 'Belum absen' }}</td>
            <td class="px-4 py-3">
              @switch($row['catatan_state'])
                @case('menunggu_acc')
                  <button type="button" @click="open('catatan', @js($row))" class="rounded-full bg-purple-100 text-purple-700 text-[10px] font-semibold px-2.5 py-1">● Menunggu ACC</button> @break
                @case('disetujui')
                  <button type="button" @click="open('catatan', @js($row))" class="rounded-full bg-green-100 text-green-700 text-[10px] font-semibold px-2.5 py-1">✓ Disetujui</button> @break
                @case('belum_absen_pulang')
                  <span class="rounded-md bg-slate-100 text-slate-500 text-[10px] font-semibold px-2.5 py-1">Belum Absen Pulang</span> @break
                @default
                  <span class="rounded-md bg-slate-100 text-slate-400 text-[10px] px-2.5 py-1">—</span>
              @endswitch
            </td>
            <td class="px-4 py-3">
              @if($row['status'])
                <span class="rounded-full text-[10px] font-semibold px-2.5 py-1 {{ $badge[$row['status']] ?? 'bg-slate-100' }}">● {{ ucfirst($row['status']) }}</span>
                @if($row['warning'])<span title="Salah satu presensi (pagi/sore) kosong" class="text-amber-500">⚠</span>@endif
              @else
                <span class="rounded-full bg-slate-100 text-slate-400 text-[10px] px-2.5 py-1">● —</span>
              @endif
            </td>
            <td class="px-4 py-3 min-w-[140px]">
              @php $b = $row['bulan']; @endphp
              @if($b && $b['persen'] !== null)
                <div class="flex items-center gap-2" title="Hadir {{ $b['hadir'] }} · Izin {{ $b['izin'] }} · Sakit {{ $b['sakit'] }} · Cuti {{ $b['cuti'] }} · Alpha {{ $b['alpha'] }} · Tanpa data {{ $b['tanpa_data'] }}">
                  <div class="h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full {{ $warnaBar($b['persen']) }}" @style(['width: ' . $b['persen'] . '%'])></div></div>
                  <span class="text-[11px] font-semibold {{ $warnaTeks($b['persen']) }}">{{ $b['persen'] }}%</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ $b['hadir'] }}/{{ $b['hari_kerja'] }} hari</div>
              @else
                <span class="text-slate-400">—</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-1.5">
                <button type="button" @click="open('status', @js($row))" class="border border-slate-300 rounded-md px-2.5 py-1 text-[10px] font-semibold leading-tight text-center hover:bg-slate-50">Ubah<br>Status</button>
                <button type="button" @click="open('qr', @js($row))" class="border border-slate-300 rounded-md px-2.5 py-1 text-[10px] font-semibold hover:bg-slate-50">▦ QR</button>
                <button type="button" @click="open('hapus', @js($row))" class="border border-red-200 text-red-600 rounded-md px-2.5 py-1 text-[10px] font-semibold hover:bg-red-50">Hapus</button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-slate-400 py-10">Tidak ada peserta ditemukan.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="flex justify-between px-4 py-3 text-[11px] text-slate-400 border-t border-slate-100">
      <span>{{ $rows->count() }} peserta ditemukan</span>
      <span>Menampilkan presensi tanggal terpilih</span>
    </div>
  </div>

  {{-- ===== POPUP: Ubah Status Presensi ===== --}}
  <div x-show="modal === 'status'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <form method="POST" action="{{ route('admin.presensi.status') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      @csrf
      {{-- FIX: nama field harus user_id (sesuai validasi controller), bukan peserta_id --}}
      <input type="hidden" name="user_id" :value="r.user_id">
      <input type="hidden" name="tanggal" :value="r.tanggal">
      <input type="hidden" name="status" :value="status">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="font-bold text-sm">Ubah Status Presensi</h3>
          <p class="text-[11px] text-slate-500" x-text="r.nama + ' — ' + r.tanggal_label"></p>
        </div>
        <button type="button" @click="modal = null" class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-xs">✕</button>
      </div>
      <div class="grid grid-cols-2 gap-2 mt-4">
        @foreach(['hadir','izin','sakit','cuti','alpha'] as $s)
          <button type="button" @click="status = '{{ $s }}'"
                  :class="status === '{{ $s }}' ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-slate-200 hover:bg-slate-50'"
                  class="border rounded-lg py-2 text-xs font-semibold">{{ ucfirst($s) }}</button>
        @endforeach
      </div>
      <label class="block text-[11px] font-semibold mt-4 mb-1">Catatan (opsional)</label>
      <textarea name="catatan_admin" rows="3" x-model="catatan" placeholder="Contoh: Izin sakit, dikonfirmasi lewat WA orang tua/guru pembimbing."
                class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="modal = null" class="border border-slate-300 rounded-lg px-4 py-2 text-xs font-semibold">Batal</button>
        <button type="submit" :disabled="!status" class="bg-blue-600 disabled:opacity-40 text-white rounded-lg px-4 py-2 text-xs font-semibold">Simpan Perubahan</button>
      </div>
    </form>
  </div>

  {{-- ===== POPUP: Catatan Harian Peserta ===== --}}
  <div x-show="modal === 'catatan'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="font-bold text-sm">Catatan Harian Peserta</h3>
          <p class="text-[11px] text-slate-500" x-text="r.tanggal_panjang"></p>
        </div>
        <button type="button" @click="modal = null" class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-xs">✕</button>
      </div>
      <div class="mt-3 flex items-center justify-between bg-slate-50 rounded-xl p-3">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 text-[10px] font-bold flex items-center justify-center" x-text="r.inisial"></div>
          <div><div class="text-xs font-bold" x-text="r.nama"></div><div class="text-[10px] text-slate-500" x-text="r.departemen"></div></div>
        </div>
        <div class="text-[10px] text-right text-slate-500">Masuk <b class="text-slate-800" x-text="r.masuk ?? '—'"></b><br>Pulang <b class="text-slate-800" x-text="r.keluar ?? '—'"></b></div>
      </div>
      <div class="flex items-center justify-between mt-4">
        <span class="text-[11px] font-semibold">Laporan kegiatan hari ini</span>
        <span x-show="r.catatan_state === 'menunggu_acc'" class="rounded-full bg-purple-100 text-purple-700 text-[10px] font-semibold px-2.5 py-0.5">● Menunggu ACC</span>
        <span x-show="r.catatan_state === 'disetujui'" class="rounded-full bg-green-100 text-green-700 text-[10px] font-semibold px-2.5 py-0.5">✓ Disetujui</span>
      </div>
      <div class="mt-2 bg-purple-50 border border-purple-100 rounded-xl p-3 text-xs text-slate-700 whitespace-pre-line min-h-[80px]">
        <template x-if="r.laporan"><span x-text="r.laporan"></span></template>
        <template x-if="!r.laporan"><i class="text-slate-400">Laporan belum diisi.</i></template>
      </div>
      <div class="grid grid-cols-2 gap-2 mt-4">
        <button type="button" @click="modal = null" class="border border-slate-300 rounded-lg py-2.5 text-xs font-semibold">Tutup</button>
        <form method="POST" :action="r.approve_url" class="contents">
          @csrf
          <button type="submit" :disabled="!r.laporan || r.catatan_state === 'disetujui'"
                  class="bg-gradient-to-r from-purple-600 to-violet-500 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg py-2.5 text-xs font-semibold"
                  x-text="!r.laporan ? 'Menunggu Laporan' : (r.catatan_state === 'disetujui' ? '✓ Sudah Disetujui' : '✓ Setujui Catatan')"></button>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== POPUP: QR Kartu ID ===== --}}
  <div x-show="modal === 'qr'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-xs p-5 text-center relative">
      <button type="button" @click="modal = null" class="absolute right-4 top-4 w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-xs">✕</button>
      <span class="inline-block bg-blue-50 text-blue-600 text-[10px] font-semibold rounded-full px-3 py-1">Identitas Peserta Magang</span>
      <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 font-bold text-sm flex items-center justify-center mx-auto mt-4" x-text="r.inisial"></div>
      <h3 class="font-bold mt-2" x-text="r.nama"></h3>
      <p class="text-xs text-slate-500" x-text="r.departemen"></p>
      <p class="text-[11px] text-slate-400">NIP: <span x-text="r.nip"></span></p>
      <div class="bg-slate-50 rounded-xl p-3 mt-3"><img :src="r.qr_url" alt="QR" class="w-44 h-44 mx-auto"></div>
      <p class="text-[10px] text-slate-400 mt-2">✔ Digital Verified Badge</p>
      <p class="text-[10px] text-slate-400">Scan QR ini di halaman presensi saat masuk dan pulang.</p>
      <a :href="r.qr_download" class="block mt-3 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg py-2.5">⇩ Download QR (PNG)</a>
    </div>
  </div>

  {{-- ===== POPUP: ACC Semua ===== --}}
  <div x-show="modal === 'accAll'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <form method="POST" action="{{ route('admin.presensi.approve-all') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      @csrf
      <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
      <input type="hidden" name="departemen" value="{{ request('departemen') }}">
      <input type="hidden" name="q" value="{{ request('q') }}">
      <h3 class="font-bold text-sm">ACC semua catatan harian?</h3>
      <p class="text-xs text-slate-600 mt-2 leading-relaxed">
        <b>{{ $stat['acc'] }} catatan</b> tanggal {{ $tanggal->translatedFormat('d F Y') }}{{ request('departemen') ? ' (' . request('departemen') . ')' : '' }}
        akan disetujui sekaligus. Peserta yang laporannya belum diisi tidak ikut di-ACC.
      </p>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="modal = null" class="border border-slate-300 rounded-lg px-4 py-2 text-xs font-semibold">Batal</button>
        <button type="submit" class="bg-gradient-to-r from-purple-600 to-violet-500 text-white rounded-lg px-4 py-2 text-xs font-semibold">✓ Ya, ACC Semua</button>
      </div>
    </form>
  </div>

  {{-- ===== POPUP: Import Excel ===== --}}
  <div x-show="modal === 'import'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <form method="POST" action="{{ route('admin.peserta.import') }}" enctype="multipart/form-data" x-data="{ f: '' }" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      @csrf
      <div class="flex justify-between items-start">
        <div><h3 class="font-bold text-sm">Import Peserta dari Excel</h3><p class="text-[11px] text-slate-500">Tambah banyak peserta sekaligus.</p></div>
        <button type="button" @click="modal = null" class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-xs">✕</button>
      </div>
      <p class="text-[11px] text-slate-500 mt-3 leading-relaxed">Kolom baris pertama: <b>STATUS, NIS/NIM, NAMA, L/P, TGL_MULAI, TGL_SELESAI, KET, UNIV/SEKOLAH, PRODI, TEMPAT PRAKERIN, DEPARTEMEN, DIVISI</b>. NIS/NIM yang sudah terdaftar akan dilewati. QR dibuat otomatis.</p>
      <a href="{{ route('admin.peserta.template') }}" class="inline-block text-[11px] font-semibold text-blue-600 mt-2">⇩ Download template</a>
      <label class="flex flex-col items-center justify-center bg-slate-100 rounded-xl py-6 mt-3 cursor-pointer text-center">
        <span class="text-xs font-semibold" x-text="f || 'Klik untuk pilih file'"></span>
        <span class="text-[10px] text-slate-400">.xlsx / .xls / .csv, maks. 5MB</span>
        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" required @change="f = $event.target.files[0]?.name ?? ''">
      </label>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="modal = null" class="border border-slate-300 rounded-lg px-4 py-2 text-xs font-semibold">Batal</button>
        <button type="submit" :disabled="!f" class="bg-blue-600 disabled:opacity-40 text-white rounded-lg px-4 py-2 text-xs font-semibold">Import</button>
      </div>
    </form>
  </div>

  {{-- ===== POPUP: Hapus Peserta ===== --}}
  <div x-show="modal === 'hapus'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <form method="POST" :action="r.hapus_url" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      @csrf @method('DELETE')
      <h3 class="font-bold text-sm">Hapus peserta?</h3>
      <p class="text-xs text-slate-600 mt-2 leading-relaxed"><b x-text="r.nama"></b> akan dihapus beserta seluruh riwayat presensi dan penugasannya. Tindakan ini tidak bisa dibatalkan.</p>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="modal = null" class="border border-slate-300 rounded-lg px-4 py-2 text-xs font-semibold">Batal</button>
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-xs font-semibold">Ya, Hapus</button>
      </div>
    </form>
  </div>

  {{-- ===== POPUP: Export Excel ===== --}}
  <div x-show="modal === 'export'" x-cloak class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4" @click.self="modal = null">
    <form method="GET" action="{{ route('admin.presensi.export') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      <div class="flex justify-between items-start">
        <div><h3 class="font-bold text-sm">Export Rekap Presensi</h3><p class="text-[11px] text-slate-500">Pilih periode dan departemen.</p></div>
        <button type="button" @click="modal = null" class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-xs">✕</button>
      </div>
      <div class="grid grid-cols-2 gap-2 mt-4">
        <label class="text-[11px] font-semibold">Dari<input type="date" name="dari" value="{{ $tanggal->toDateString() }}" class="{{ $inp }} w-full mt-1"></label>
        <label class="text-[11px] font-semibold">Sampai<input type="date" name="sampai" value="{{ $tanggal->toDateString() }}" class="{{ $inp }} w-full mt-1"></label>
      </div>
      <label class="block text-[11px] font-semibold mt-3">Departemen
        <select name="departemen" class="{{ $inp }} w-full mt-1">
          <option value="">Semua Departemen</option>
          @foreach($departemen as $d)<option @selected(request('departemen')===$d)>{{ $d }}</option>@endforeach
        </select>
      </label>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="modal = null" class="border border-slate-300 rounded-lg px-4 py-2 text-xs font-semibold">Batal</button>
        <button type="submit" class="bg-green-600 text-white rounded-lg px-4 py-2 text-xs font-semibold">Download Excel</button>
      </div>
    </form>
  </div>
</div>

<script>
  function presensi() {
    return {
      modal: null, r: {}, status: '', catatan: '',
      open(m, row) { this.r = row; this.status = row.status ?? ''; this.catatan = row.catatan_admin ?? ''; this.modal = m; },
    };
  }
</script>
@endsection