@extends('layouts.auth')

@section('body-class', 'mode-landing')
@section('badge', 'Portal Pendaftaran')

@section('konten')
    {{-- Di mobile bagian ini disembunyikan CSS (mode-landing menyembunyikan .formside), --}}
    {{-- yang tampil cuma tombol Login/Sign Up di panel brand. Di desktop, form ini langsung terlihat. --}}
    @include('auth.partials.form-masuk')
@endsection
