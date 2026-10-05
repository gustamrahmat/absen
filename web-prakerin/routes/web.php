<?php

// File ini MENGGANTIKAN routes/web.php bawaan (sudah menggabungkan auth + presensi + laporan + tugas).

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\KartuIdController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TugasController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'tampilLanding'])->name('landing');
    Route::get('/daftar', [AuthController::class, 'tampilDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->name('daftar.store')->middleware('throttle:20,1');
    Route::get('/login', [AuthController::class, 'tampilMasuk'])->name('login');
    // Batas per IP. Kantor sering berbagi 1 IP publik, jadi angkanya sengaja tidak terlalu kecil.
    Route::post('/login', [AuthController::class, 'masuk'])->name('login.store')->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'keluar'])->name('logout');

    // -- Presensi --
    Route::get('/presensi', [AttendanceController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/scan', [AttendanceController::class, 'scan'])->name('presensi.scan');
    Route::post('/presensi/verifikasi', [AttendanceController::class, 'verifikasiUuid'])->name('presensi.verifikasi')->middleware('throttle:30,1');
    Route::post('/presensi/simpan', [AttendanceController::class, 'simpan'])->name('presensi.simpan')->middleware('throttle:10,1');
    Route::get('/presensi/{sesiPresensi}/foto', [AttendanceController::class, 'foto'])->name('presensi.foto');

    // -- Laporan --
    Route::get('/laporan', [ReportController::class, 'riwayat'])->name('laporan.riwayat');
    Route::get('/laporan/{sesiPresensi}/isi', [ReportController::class, 'form'])->name('laporan.form');
    Route::post('/laporan/{sesiPresensi}/simpan', [ReportController::class, 'store'])->name('laporan.store');

    // -- Admin: cetak kartu ID (peran dicek di controller) --
    Route::get('/admin/kartu-id', [KartuIdController::class, 'cetak'])->name('kartu.cetak');

    // -- Tugas --
    Route::post('/tugas', [TugasController::class, 'simpan'])->name('tugas.simpan')->middleware('throttle:10,1');
    Route::get('/tugas/{tugas}/unduh', [TugasController::class, 'unduh'])->name('tugas.unduh');
});
