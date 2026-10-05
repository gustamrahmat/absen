<?php

namespace App\Http\Controllers;

use App\Models\LaporanHarian;
use App\Models\SesiPresensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // ============ HALAMAN: FORM LAPORAN HARIAN (wajib setelah presensi sore) ============
    public function form(SesiPresensi $sesiPresensi)
    {
        abort_unless($sesiPresensi->user_id === Auth::id(), 403);
        abort_unless($sesiPresensi->sesi === 'sore', 404, 'Laporan harian hanya untuk presensi sesi sore.');

        if ($sesiPresensi->laporanHarian) {
            return redirect()->route('laporan.riwayat')->with('info', 'Laporan untuk presensi ini sudah pernah dikirim.');
        }

        return view('laporan.form', compact('sesiPresensi'));
    }

    // ============ API: SIMPAN LAPORAN HARIAN ============
    public function store(Request $request, SesiPresensi $sesiPresensi)
    {
        abort_unless($sesiPresensi->user_id === Auth::id(), 403);

        if ($sesiPresensi->laporanHarian) {
            return response()->json(['ok' => false, 'pesan' => 'Laporan untuk presensi ini sudah pernah dikirim.'], 409);
        }

        // Satu teks: kegiatan hari ini + kendala (kalau ada) ditulis sekaligus.
        $data = $request->validate([
            'laporan' => ['required', 'string', 'min:100', 'max:1000'],
        ], [
            'laporan.required' => 'Laporan harian wajib diisi.',
            'laporan.min' => 'Laporan harian minimal 100 karakter.',
            'laporan.max' => 'Laporan harian maksimal 1000 karakter.',
        ]);

        $laporan = LaporanHarian::create([
            'sesi_presensi_id' => $sesiPresensi->id,
            'laporan' => $data['laporan'],
            'status_review' => 'disetujui', // kebijakan sekarang: laporan auto-ACC
        ]);

        return response()->json(['ok' => true, 'laporanId' => $laporan->id]);
    }

    // ============ HALAMAN: RIWAYAT LAPORAN ============
    public function riwayat()
    {
        $laporan = LaporanHarian::whereHas('sesiPresensi', fn ($q) => $q->where('user_id', Auth::id()))
            ->with('sesiPresensi')
            ->latest('created_at')
            ->paginate(10);

        return view('laporan.riwayat', compact('laporan'));
    }
}
