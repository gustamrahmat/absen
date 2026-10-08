@extends('layouts.auth')

@section('judul', 'Masuk Akun')
@section('body-class', 'mode-form')
@section('badge', 'Login Akun')

@section('konten')
    @include('auth.partials.form-masuk')
@endsection
