@extends('layouts.admin')
@section('title','Tambah Peserta Magang')
@section('content')
@php
$inp='w-full border border-slate-200 rounded-lg px-3 py-2.5 text-xs bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-200';
$lbl='block text-[11px] font-semibold mb-1';
@endphp
<a href="{{ route('admin.presensi.index') }}" class="text-[11px] text-slate-500">← Kembali</a>
<h1 class="text-2xl font-extrabold mt-1">Tambah Peserta Magang</h1>
<p class="text-xs text-slate-500 mt-1">Data langsung disimpan ke <b>data_magang</b> dan akun peserta dibuat di <b>users</b>.</p>

<form method="POST" action="{{ route('admin.peserta.store') }}" enctype="multipart/form-data"
      x-data="{tipe:'{{ old('tipe_magang','smk') }}',foto:'',cv:'',surat:''}"
      class="max-w-3xl mx-auto mt-6 bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
@csrf
@if($errors->any())
<div class="mb-4 rounded-lg bg-red-50 text-red-700 text-xs px-4 py-2"><ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="grid grid-cols-2 bg-slate-100 rounded-lg p-1 text-xs font-semibold">
<button type="button" @click="tipe='smk'" :class="tipe==='smk'?'bg-white shadow rounded-md':'text-slate-500'" class="py-2">Peserta SMK</button>
<button type="button" @click="tipe='kuliah'" :class="tipe==='kuliah'?'bg-white shadow rounded-md':'text-slate-500'" class="py-2">Sarjana / Diploma</button>
</div>
<input type="hidden" name="tipe_magang" :value="tipe">

<h4 class="text-[10px] font-bold tracking-widest text-blue-600 mt-5 mb-3">● DATA PESERTA</h4>
<div class="grid md:grid-cols-2 gap-3">
<div><label class="{{ $lbl }}">Nama Lengkap *</label><input name="nama" value="{{old('nama')}}" class="{{$inp}}" required></div>
<div><label class="{{ $lbl }}">Email Akun *</label><input type="email" name="email" value="{{old('email')}}" class="{{$inp}}" placeholder="peserta@email.com" required></div>
<div><label class="{{ $lbl }}">No. KTP *</label><input name="no_ktp" value="{{old('no_ktp')}}" maxlength="16" class="{{$inp}}" required></div>
<div><label class="{{ $lbl }}">No. WA *</label><input name="no_wa" value="{{old('no_wa')}}" class="{{$inp}}" required></div>
<div><label class="{{ $lbl }}">Tempat Lahir *</label><input name="tempat_lahir" value="{{old('tempat_lahir')}}" class="{{$inp}}" required></div>
<div><label class="{{ $lbl }}">Tanggal Lahir *</label><input type="date" name="tanggal_lahir" value="{{old('tanggal_lahir')}}" class="{{$inp}}" required></div>
<div class="md:col-span-2"><label class="{{ $lbl }}">Alamat *</label><textarea name="alamat" rows="2" class="{{$inp}}" required>{{old('alamat')}}</textarea></div>
</div>

<h4 class="text-[10px] font-bold tracking-widest text-blue-600 mt-6 mb-3">● DATA SEKOLAH / KAMPUS</h4>
<div class="grid md:grid-cols-2 gap-3">
<div><label class="{{ $lbl }}">Asal Sekolah / Universitas *</label><input name="asal_sekolah" value="{{old('asal_sekolah')}}" class="{{$inp}}" required></div>
<div><label class="{{ $lbl }}">Jurusan / Prodi *</label><input name="jurusan" value="{{old('jurusan')}}" class="{{$inp}}" required></div>
<div><label class="{{ $lbl }}">NIS / NIM</label><input name="nis_nim" value="{{old('nis_nim')}}" class="{{$inp}}"></div>
<div><label class="{{ $lbl }}">Jenis Kelamin</label><select name="jenis_kelamin" class="{{$inp}}"><option value="">-</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
<div><label class="{{ $lbl }}">Pembimbing</label><input name="pembimbing" value="{{old('pembimbing')}}" class="{{$inp}}"></div>
<div><label class="{{ $lbl }}">No. WA Pembimbing</label><input name="no_wa_pembimbing" value="{{old('no_wa_pembimbing')}}" class="{{$inp}}"></div>
</div>

<h4 class="text-[10px] font-bold tracking-widest text-blue-600 mt-6 mb-3">● PENEMPATAN</h4>
<div class="grid md:grid-cols-2 gap-3">
<div><label class="{{$lbl}}">Departemen *</label><select name="departemen_id" class="{{$inp}}" required><option value="">Pilih departemen</option>@foreach($departemen as $d)<option value="{{$d->id}}" @selected(old('departemen_id')==$d->id)>{{$d->nama}}</option>@endforeach</select></div>
<div><label class="{{$lbl}}">Divisi</label><input name="divisi" value="{{old('divisi')}}" class="{{$inp}}"></div>
<div><label class="{{$lbl}}">Periode Magang</label><input name="periode_magang" value="{{old('periode_magang')}}" class="{{$inp}}" placeholder="Sept - Des 2026"></div>
<div><label class="{{$lbl}}">Tempat Prakerin</label><input name="tempat_prakerin" value="{{old('tempat_prakerin')}}" class="{{$inp}}"></div>
<div><label class="{{$lbl}}">Tanggal Mulai</label><input type="date" name="tgl_mulai" value="{{old('tgl_mulai')}}" class="{{$inp}}"></div>
<div><label class="{{$lbl}}">Tanggal Selesai</label><input type="date" name="tgl_selesai" value="{{old('tgl_selesai')}}" class="{{$inp}}"></div>
</div>

<h4 class="text-[10px] font-bold tracking-widest text-blue-600 mt-6 mb-3">● BERKAS</h4>
<div class="grid md:grid-cols-3 gap-3">
<div><label class="{{$lbl}}">Foto Pas *</label><input type="file" name="foto_pas" accept=".jpg,.jpeg,.png" class="{{$inp}}" required></div>
<div><label class="{{$lbl}}">CV</label><input type="file" name="cv_path" accept=".pdf,.doc,.docx" class="{{$inp}}"></div>
<div><label class="{{$lbl}}">Surat Permohonan</label><input type="file" name="surat_permohonan_path" accept=".pdf,.jpg,.jpeg,.png" class="{{$inp}}"></div>
</div>

<div class="mt-3"><label class="{{$lbl}}">Keterangan</label><textarea name="keterangan" rows="2" class="{{$inp}}">{{old('keterangan')}}</textarea></div>
<div class="grid grid-cols-[1fr_2fr] gap-3 mt-6">
<a href="{{route('admin.presensi.index')}}" class="text-center border border-slate-300 rounded-lg py-2.5 text-xs font-semibold">Batal</a>
<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 text-xs font-semibold">Simpan Peserta</button>
</div>
</form>
@endsection
