<?php

namespace App\Http\Controllers;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KartuIdController extends Controller
{
    // AdminOnly middleware sudah memastikan admin berasal dari tabel admins.
    public function cetak(Request $request)
    {
        // Isi uuid_kartu untuk peserta lama yang masih NULL,
        // supaya mereka ikut tampil dan QR-nya bisa dibuat.
        User::where('peran', 'peserta')
            ->whereNull('uuid_kartu')
            ->get()
            ->each(function (User $u) {
                $u->uuid_kartu = (string) Str::uuid();
                $u->save();
            });

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