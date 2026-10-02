<?php

namespace App\Http\Controllers;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KartuIdController extends Controller
{
    // ============ HALAMAN: CETAK KARTU ID (hasil cetak = PDF, satu halaman-set per divisi) ============
    // ?divisi= kosong -> semua divisi (halaman baru tiap divisi) | angka -> id departemen | tanpa -> peserta tanpa divisi
    // QR HANYA berisi UUID (bukan data pribadi). QR digambar di peramban; cetak lewat "Simpan sebagai PDF".
    public function cetak(Request $request)
    {
        abort_unless(in_array(Auth::user()->peran, config('prakerin.peran_admin'), true), 403);

        $pilihan = (string) $request->query('divisi', '');

        $query = User::where('peran', 'peserta')
            ->whereNotNull('uuid_kartu')
            ->whereIn('status_akun', ['diterima', 'aktif'])
            ->with('departemen')
            ->orderBy('name');

        if ($pilihan === 'tanpa') {
            $query->whereNull('departemen_id');
        } elseif (ctype_digit($pilihan)) {
            $query->where('departemen_id', (int) $pilihan);
        }

        $kelompok = $query->get()
            ->groupBy(fn ($u) => optional($u->departemen)->nama ?? 'Tanpa Divisi')
            ->sortKeys();

        $daftarDepartemen = Departemen::orderBy('nama')->get();

        return view('kartu.cetak', compact('kelompok', 'daftarDepartemen', 'pilihan'));
    }
}
