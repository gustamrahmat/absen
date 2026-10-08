<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TugasController;
use App\Http\Controllers\KartuIdController;
use App\Http\Controllers\Admin\PresensiController;
use App\Http\Controllers\Admin\PesertaController;
use App\Http\Controllers\Admin\PesertaKelolaController;
use App\Http\Controllers\Admin\PresensiExportController;

/*
|--------------------------------------------------------------------------
| LANDING
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'tampilLanding'])->name('landing');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'tampilMasuk'])->name('login');
Route::post('/login', [AuthController::class, 'masuk'])->name('login.store');
Route::get('/daftar', [AuthController::class, 'tampilDaftar'])->name('daftar');
Route::post('/daftar', [AuthController::class, 'daftar'])->name('daftar.store');

/*
|--------------------------------------------------------------------------
| PESERTA
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'keluar'])->name('logout');

    Route::get('/presensi', [AttendanceController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/scan', [AttendanceController::class, 'scan'])->name('presensi.scan');

    Route::post('/presensi/verifikasi', [AttendanceController::class, 'verifikasiUuid'])
        ->name('presensi.verifikasi')
        ->middleware('throttle:30,1');

    Route::post('/presensi/simpan', [AttendanceController::class, 'simpan'])
        ->name('presensi.simpan')
        ->middleware('throttle:10,1');

    Route::get('/presensi/{sesiPresensi}/foto', [AttendanceController::class, 'foto'])->name('presensi.foto');

    Route::get('/laporan', [ReportController::class, 'riwayat'])->name('laporan.riwayat');
    Route::get('/laporan/{sesiPresensi}/isi', [ReportController::class, 'form'])->name('laporan.form');
    Route::post('/laporan/{sesiPresensi}/simpan', [ReportController::class, 'store'])->name('laporan.store');

    Route::post('/tugas', [TugasController::class, 'simpan'])
        ->name('tugas.simpan')
        ->middleware('throttle:10,1');

    Route::get('/tugas/{tugas}/unduh', [TugasController::class, 'unduh'])->name('tugas.unduh');
});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware('admin')
    ->group(function () {

        // Logout admin (nama lengkap: admin.logout)
        Route::post('/logout', function (Request $request) {
            foreach (['web', 'admin'] as $guard) {
                if (config("auth.guards.$guard")) {
                    Auth::guard($guard)->logout();
                }
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');

        Route::get('/kartu-id', [KartuIdController::class, 'cetak'])->name('kartu.cetak');

        // Presensi
        Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
        Route::post('/presensi/status', [PresensiController::class, 'updateStatus'])->name('presensi.status');
        Route::post('/presensi/acc-semua', [PresensiController::class, 'approveSemua'])->name('presensi.approve-all');
        Route::post('/presensi/{presensi}/approve', [PresensiController::class, 'approve'])->name('presensi.approve');
        Route::get('/presensi-export', [PresensiExportController::class, 'download'])->name('presensi.export');

        // Peserta
        Route::get('/peserta/create', [PesertaController::class, 'create'])->name('peserta.create');
        Route::post('/peserta', [PesertaController::class, 'store'])->name('peserta.store');
        Route::get('/peserta/{peserta}/qr.png', [PesertaController::class, 'qrImage'])->name('peserta.qr.image');
        Route::get('/peserta/{peserta}/qr/download', [PesertaController::class, 'qrDownload'])->name('peserta.qr.download');

        Route::post('/peserta/import', [PesertaKelolaController::class, 'import'])->name('peserta.import');
        Route::get('/peserta/template', [PesertaKelolaController::class, 'template'])->name('peserta.template');
        Route::delete('/peserta/{peserta}', [PesertaKelolaController::class, 'destroy'])->name('peserta.destroy');
    });