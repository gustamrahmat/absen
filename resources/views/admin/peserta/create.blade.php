@extends('layouts.admin')

@section('title', 'Tambah Peserta')

@section('content')

@php
    $inp = 'w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400';
    $lbl = 'block text-xs font-semibold text-slate-700 mb-1.5';
@endphp

<div class="max-w-4xl mx-auto">

    {{-- HEADER --}}
    <div class="mb-6">

        <a href="{{ route('admin.presensi.index') }}"
           class="inline-flex items-center text-xs text-slate-500 hover:text-blue-600 mb-3">
            ← Kembali
        </a>

        <h1 class="text-2xl font-extrabold text-slate-800">
            Tambah Peserta
        </h1>

        <p class="text-sm text-slate-500 mt-1">
            Tambahkan peserta yang akan menggunakan sistem presensi.
        </p>

    </div>


    {{-- FORM --}}
    <form method="POST"
          action="{{ route('admin.peserta.store') }}"
          class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">

        @csrf


        {{-- ERROR --}}
        @if($errors->any())
            <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                <div class="font-semibold mb-1">
                    Data belum dapat disimpan:
                </div>

                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- DATA PESERTA --}}
        <div class="mb-6">

            <div class="flex items-center gap-2 mb-4">
                <div class="w-1.5 h-5 bg-blue-600 rounded-full"></div>

                <h2 class="text-sm font-bold text-slate-800">
                    Data Peserta
                </h2>
            </div>


            <div class="grid md:grid-cols-2 gap-4">

                {{-- NAMA --}}
                <div>
                    <label class="{{ $lbl }}">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="nama"
                        value="{{ old('nama') }}"
                        class="{{ $inp }}"
                        placeholder="Masukkan nama lengkap"
                        required
                    >
                </div>


                {{-- EMAIL --}}
                <div>
                    <label class="{{ $lbl }}">
                        Email <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="{{ $inp }}"
                        placeholder="peserta@email.com"
                        required
                    >
                </div>


                {{-- NO WA --}}
                <div>
                    <label class="{{ $lbl }}">
                        No. WhatsApp
                    </label>

                    <input
                        type="text"
                        name="no_wa"
                        value="{{ old('no_wa') }}"
                        class="{{ $inp }}"
                        placeholder="08xxxxxxxxxx"
                    >
                </div>


                {{-- DEPARTEMEN --}}
                <div>
                    <label class="{{ $lbl }}">
                        Departemen <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="departemen_id"
                        class="{{ $inp }}"
                        required
                    >

                        <option value="">
                            Pilih departemen
                        </option>

                        @foreach($departemen as $d)

                            <option
                                value="{{ $d->id }}"
                                @selected(old('departemen_id') == $d->id)
                            >
                                {{ $d->nama }}
                            </option>

                        @endforeach

                    </select>
                </div>

            </div>

        </div>


        {{-- PERIODE PRESENSI --}}
        <div class="mb-6">

            <div class="flex items-center gap-2 mb-4">
                <div class="w-1.5 h-5 bg-blue-600 rounded-full"></div>

                <h2 class="text-sm font-bold text-slate-800">
                    Periode Presensi
                </h2>
            </div>


            <div class="grid md:grid-cols-2 gap-4">

                {{-- TANGGAL MULAI --}}
                <div>
                    <label class="{{ $lbl }}">
                        Tanggal Mulai <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="tgl_mulai"
                        value="{{ old('tgl_mulai') }}"
                        class="{{ $inp }}"
                        required
                    >

                    <p class="text-[11px] text-slate-400 mt-1">
                        Tanggal mulai peserta melakukan presensi.
                    </p>
                </div>


                {{-- TANGGAL SELESAI --}}
                <div>
                    <label class="{{ $lbl }}">
                        Tanggal Selesai <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="tgl_selesai"
                        value="{{ old('tgl_selesai') }}"
                        class="{{ $inp }}"
                        required
                    >

                    <p class="text-[11px] text-slate-400 mt-1">
                        Tanggal terakhir peserta melakukan presensi.
                    </p>
                </div>

            </div>

        </div>


        {{-- INFO --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-6">

            <div class="flex gap-3">

                <div class="text-blue-600 text-lg">
                    ℹ
                </div>

                <div>

                    <p class="text-xs font-semibold text-blue-800">
                        Informasi
                    </p>

                    <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                        Setelah peserta ditambahkan, akun peserta akan dibuat
                        dan peserta dapat digunakan untuk sistem presensi.
                    </p>

                </div>

            </div>

        </div>


        {{-- BUTTON --}}
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">

            <a
                href="{{ route('admin.presensi.index') }}"
                class="px-5 py-2.5 border border-slate-300 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Batal
            </a>

            <button
                type="submit"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm"
            >
                + Tambah Peserta
            </button>

        </div>

    </form>

</div>

@endsection