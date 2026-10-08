@extends('layouts.auth')

@section('judul', 'Daftar Akun')
@section('body-class', 'mode-form')
@section('badge', 'Pendaftaran Akun Baru')

@section('konten')
    @include('auth.partials.form-daftar')
@endsection
