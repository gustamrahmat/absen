<?php

namespace App\Services;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

// Satu-satunya tempat yang menjawab "apakah tanggal ini hari kerja WAJIB?".
// Aturan: hari kerja pengganti (tanggal_pengganti) = wajib > hari libur (tabel hari_libur) = tidak wajib
//         > hari kerja biasa (config prakerin.hari_kerja, Senin-Jumat) = wajib > selain itu tidak wajib.
// Di hari tidak wajib presensi tetap boleh, tetapi selalu menunggu persetujuan admin.
// Seluruh tabel (hanya puluhan baris per tahun) dibaca SEKALI per permintaan, bukan sekali per tanggal.
class KalenderKerja
{
    private static ?array $data = null;

    // Dipakai kalau tabel diubah di tengah proses yang sama (mis. di tes).
    public static function reset(): void
    {
        self::$data = null;
    }

    private static function data(): array
    {
        if (self::$data === null) {
            $libur = [];
            $pengganti = [];
            try {
                foreach (HariLibur::all() as $h) {
                    $libur[$h->tanggal->toDateString()] = $h;
                    if ($h->tanggal_pengganti) {
                        $pengganti[$h->tanggal_pengganti->toDateString()] = $h;
                    }
                }
            } catch (QueryException $e) {
                // Tabel belum ada (migration belum dijalankan): anggap tidak ada libur, jangan matikan halaman presensi.
                report($e);
            }
            self::$data = ['libur' => $libur, 'pengganti' => $pengganti];
        }

        return self::$data;
    }

    public static function hariKerja(Carbon $tanggal): bool
    {
        $kunci = $tanggal->toDateString();
        $d = self::data();

        if (isset($d['pengganti'][$kunci])) {
            return true;
        }
        if (isset($d['libur'][$kunci])) {
            return false;
        }

        return in_array($tanggal->dayOfWeekIso, config('prakerin.hari_kerja'), true);
    }

    // Nama hari libur pada tanggal ini (null kalau bukan hari libur terdaftar).
    public static function namaLibur(Carbon $tanggal): ?string
    {
        $kunci = $tanggal->toDateString();
        $d = self::data();

        if (isset($d['pengganti'][$kunci])) {
            return null;
        }

        return isset($d['libur'][$kunci]) ? $d['libur'][$kunci]->nama : null;
    }

    // Keterangan singkat untuk tooltip kalender / halaman scan.
    public static function catatan(Carbon $tanggal): ?string
    {
        $kunci = $tanggal->toDateString();
        $d = self::data();

        if (isset($d['pengganti'][$kunci])) {
            $h = $d['pengganti'][$kunci];
            return 'Hari kerja pengganti: ' . $h->nama . ' (' . $h->tanggal->translatedFormat('d F Y') . ')';
        }

        return isset($d['libur'][$kunci]) ? $d['libur'][$kunci]->nama : null;
    }

    // Hari libur yang tanggalnya ATAU hari penggantinya jatuh di rentang ini (untuk daftar di bawah kalender).
    public static function daftarRentang(Carbon $awal, Carbon $akhir): array
    {
        $hasil = [];
        foreach (self::data()['libur'] as $h) {
            $diDalam = $h->tanggal->betweenIncluded($awal->copy()->startOfDay(), $akhir->copy()->endOfDay())
                || ($h->tanggal_pengganti
                    && $h->tanggal_pengganti->betweenIncluded($awal->copy()->startOfDay(), $akhir->copy()->endOfDay()));
            if ($diDalam) {
                $hasil[] = $h;
            }
        }
        usort($hasil, fn ($a, $b) => $a->tanggal <=> $b->tanggal);

        return $hasil;
    }
}
