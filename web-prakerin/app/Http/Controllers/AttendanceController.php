<?php

namespace App\Http\Controllers;

use App\Models\LogPerubahanStatus;
use App\Models\SesiPresensi;
use App\Models\Tugas;
use App\Services\KalenderKerja;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    // Aturan (jendela waktu, hari kerja, titik pos, batas akurasi) ada di config/prakerin.php.

    // Apakah tanggal ini hari kerja WAJIB? Aturannya ada di App\Services\KalenderKerja:
    // hari kerja pengganti > hari libur (tabel hari_libur) > Senin-Jumat.
    // Di luar hari kerja (Sabtu, Minggu, libur) presensi TETAP boleh, tapi tidak wajib dan
    // selalu menunggu persetujuan admin (lihat simpan() dan tentukanStatusHari()).
    public static function hariKerja(Carbon $tanggal): bool
    {
        return KalenderKerja::hariKerja($tanggal);
    }

    // ============ HALAMAN: KALENDER REKAP + TOMBOL SCAN + UPLOAD TUGAS ============
    public function index(Request $request)
    {
        $user = Auth::user();

        // '!' di depan format = jangan ikut hari ini (tanpa ini, tanggal 31 + bulan 30 hari melompat ke bulan berikutnya)
        try {
            $bulan = Carbon::createFromFormat('!Y-m', (string) $request->query('bulan', now()->format('Y-m'))) ?: now();
        } catch (\Throwable $e) {
            $bulan = now();
        }
        $bulan = $bulan->startOfMonth();
        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth();

        $sesiBulanIni = SesiPresensi::where('user_id', $user->id)
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->get()
            ->groupBy(fn ($s) => $s->tanggal->toDateString());

        $logStatusBulanIni = LogPerubahanStatus::where('user_id', $user->id)
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->get()
            ->keyBy(fn ($l) => $l->tanggal->toDateString());

        // Hari sebelum presensi pertama peserta tidak dihitung alpha (mis. akun dibuat jauh sebelum mulai magang).
        $tanggalPertama = SesiPresensi::where('user_id', $user->id)->min('tanggal');
        $tanggalMulai = $tanggalPertama ? Carbon::parse($tanggalPertama)->startOfDay() : null;

        $hariDalamBulan = [];
        $hitung = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];

        for ($tgl = $awal->copy(); $tgl->lte($akhir); $tgl->addDay()) {
            $key = $tgl->toDateString();
            $status = $this->tentukanStatusHari(
                $tgl->copy(),
                $sesiBulanIni->get($key, collect()),
                $logStatusBulanIni->get($key),
                $tanggalMulai
            );
            $hariDalamBulan[$key] = $status + ['catatan' => KalenderKerja::catatan($tgl)]; // catatan = nama libur / hari pengganti

            if (array_key_exists($status['kode'], $hitung)) {
                $hitung[$status['kode']]++;
            }
        }

        // Presensi sore yang laporan hariannya belum diisi (laporan wajib setelah absen pulang).
        $laporanTertunda = SesiPresensi::where('user_id', $user->id)
            ->where('sesi', 'sore')
            ->doesntHave('laporanHarian')
            ->orderByDesc('tanggal')
            ->limit(3)
            ->get();

        $riwayatTugas = Tugas::where('user_id', $user->id)->latest()->limit(5)->get();

        $daftarLibur = KalenderKerja::daftarRentang($awal, $akhir);

        return view('presensi.index', compact('bulan', 'hariDalamBulan', 'hitung', 'laporanTertunda', 'riwayatTugas', 'daftarLibur'));
    }

    // Menentukan status satu hari untuk kalender.
    // Prioritas: status manual HRD (log_perubahan_status) > ada/tidaknya presensi > kelengkapan sesi.
    // Tanda: ✓ lengkap & sah | … hari berjalan (sudah pagi, belum sore) | ! hanya 1 sesi | ⏳ lengkap tapi menunggu admin | ✕ alpha/ditolak.
    private function tentukanStatusHari(Carbon $tanggal, Collection $sesiHari, $logStatus, ?Carbon $tanggalMulai): array
    {
        if ($logStatus) {
            $tandaManual = match ($logStatus->status_baru) {
                'izin' => 'I', 'sakit' => 'S', 'cuti' => 'C', 'alpha' => '✕', default => '',
            };
            return ['kode' => $logStatus->status_baru, 'tanda' => $tandaManual];
        }

        $wajib = self::hariKerja($tanggal); // false = Sabtu/Minggu/libur (presensi opsional, selalu menunggu admin)

        // Tidak ada presensi sama sekali.
        if ($sesiHari->isEmpty()) {
            if (! $wajib) {
                // Libur terdaftar diberi warna sendiri; akhir pekan biasa tetap polos. Keduanya bukan alpha.
                return ['kode' => KalenderKerja::namaLibur($tanggal) ? 'libur_resmi' : 'libur', 'tanda' => ''];
            }
            if ($tanggal->isFuture() || $tanggal->isToday()) {
                return ['kode' => 'kosong', 'tanda' => ''];
            }
            if ($tanggalMulai === null || $tanggal->lt($tanggalMulai)) {
                return ['kode' => 'kosong', 'tanda' => ''];
            }
            return ['kode' => 'alpha', 'tanda' => '✕'];
        }

        // Ditolak admin: hari kerja -> alpha; hari tidak wajib -> abu-abu, bukan alpha.
        if ($sesiHari->contains('status_persetujuan', 'ditolak')) {
            return $wajib ? ['kode' => 'alpha', 'tanda' => '✕'] : ['kode' => 'ditolak', 'tanda' => '✕'];
        }

        $adaPagi = $sesiHari->contains('sesi', 'pagi');
        $adaSore = $sesiHari->contains('sesi', 'sore');

        // Pagi + sore lengkap. Semua sesuai waktu (auto) atau sudah disetujui admin -> langsung ✓.
        // Masih ada yang 'menunggu' (di luar jam sesi / hari tidak wajib) -> ⏳ sampai admin memutuskan.
        if ($adaPagi && $adaSore) {
            return $sesiHari->contains('status_persetujuan', 'menunggu')
                ? ['kode' => 'menunggu', 'tanda' => '⏳']
                : ['kode' => 'hadir', 'tanda' => '✓'];
        }

        // Hanya 1 sesi. Admin sudah menyetujui (mis. izin setengah hari) -> ✓.
        if ($sesiHari->contains('status_persetujuan', 'disetujui')) {
            return ['kode' => 'hadir', 'tanda' => '✓'];
        }

        // Hanya sore: pagi sudah lewat (jendela sore mulai saat jendela pagi tutup) -> izin setengah hari / lupa pagi.
        if ($adaSore) {
            return ['kode' => 'satu_sesi', 'tanda' => '!'];
        }

        // Hanya pagi: selama hari berjalan (sebelum sesi sore tutup) -> … ; setelah itu sore terlewat -> !
        $jamSoreTutup = $tanggal->copy()->setTime(config('prakerin.jendela.sore.selesai'), 0);
        if ($tanggal->isToday() && now()->lt($jamSoreTutup)) {
            return ['kode' => 'progres', 'tanda' => '…'];
        }
        return ['kode' => 'satu_sesi', 'tanda' => '!'];
    }

    // ============ HALAMAN: SCAN ============
    public function scan()
    {
        // Presensi dibuka setiap hari. Di luar hari kerja halaman hanya menampilkan pemberitahuan.
        return view('presensi.scan', [
            'posisi' => config('prakerin.pos'),
            'jendela' => config('prakerin.jendela'),
            'hariWajib' => self::hariKerja(now()),
            'catatanHari' => KalenderKerja::catatan(now()),
        ]);
    }

    // ============ API: CEK KARTU ID (UUID dari QR harus milik akun yang login) ============
    public function verifikasiUuid(Request $request)
    {
        $user = Auth::user();
        $uuid = trim((string) $request->input('uuid'));

        if (blank($user->uuid_kartu)) {
            return response()->json(['valid' => false, 'pesan' => 'Akunmu belum memiliki Kartu ID. Hubungi admin/HRD.']);
        }

        if (! hash_equals((string) $user->uuid_kartu, $uuid)) {
            return response()->json(['valid' => false, 'pesan' => 'Kartu ID ini bukan milik akun yang sedang login.']);
        }

        return response()->json([
            'valid' => true,
            'nama' => $user->name,
            'nomorInduk' => $user->nomor_induk,
            'divisi' => optional($user->departemen)->nama,
        ]);
    }

    // ============ API: SIMPAN HASIL PRESENSI (1 sesi) ============
    // Semua aturan dicek ULANG di server (kartu, lokasi, akurasi, sesi ganda, foto).
    // Jam absen = jam server, bukan jam dari peramban.
    public function simpan(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'uuid' => ['required', 'string'],
            'sesi' => ['required', 'in:pagi,sore'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['required', 'numeric', 'min:0'],
            'deviceId' => ['nullable', 'string', 'max:100'],
            'fotoBase64' => ['required_if:sesi,pagi', 'nullable', 'string'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'pesan' => 'Data presensi tidak lengkap atau tidak valid.'], 422);
        }
        $data = $validator->validated();
        $sekarang = now();

        // 1) Hari kerja: bukan syarat. Di Sabtu/Minggu/libur presensi tetap diterima,
        //    tapi tidak otomatis disetujui -- menunggu persetujuan admin.
        $hariWajib = self::hariKerja($sekarang);

        // 2) Kartu ID harus milik akun yang login
        if (blank($user->uuid_kartu) || ! hash_equals((string) $user->uuid_kartu, trim($data['uuid']))) {
            return response()->json(['ok' => false, 'pesan' => 'Kartu ID ini bukan milik akun yang sedang login.'], 403);
        }

        // 3) Akurasi GPS & zona absen
        if ((float) $data['akurasi'] > config('prakerin.maks_akurasi_meter')) {
            return response()->json(['ok' => false, 'pesan' => 'Akurasi GPS kurang baik. Coba lagi di tempat yang lebih terbuka.'], 422);
        }
        $terdekat = $this->posTerdekat((float) $data['lat'], (float) $data['lng']);
        if ($terdekat['jarak'] > $terdekat['pos']['radius']) {
            return response()->json(['ok' => false, 'pesan' => 'Anda tidak berada di salah satu zona absen yang terdaftar.'], 422);
        }

        // 4) Tolak presensi ganda (tidak menimpa jam absen yang sudah tercatat)
        $sudahAda = SesiPresensi::with('laporanHarian')
            ->where(['user_id' => $user->id, 'tanggal' => $sekarang->toDateString(), 'sesi' => $data['sesi']])
            ->first();
        if ($sudahAda) {
            $respons = ['ok' => false, 'pesan' => 'Presensi sesi ' . $data['sesi'] . ' hari ini sudah tercatat.'];
            if ($data['sesi'] === 'sore' && ! $sudahAda->laporanHarian) {
                $respons['pesan'] .= ' Laporan harianmu belum diisi, mengalihkan ke formulir laporan.';
                $respons['laporanUrl'] = route('laporan.form', $sudahAda);
            }
            return response()->json($respons, 409);
        }

        // 5) Di luar jendela sesi ATAU bukan hari kerja -> tetap tercatat, tapi menunggu persetujuan admin/HRD
        $jam = $sekarang->hour + $sekarang->minute / 60;
        $jendela = config('prakerin.jendela.' . $data['sesi']);
        $dalamJendela = $jam >= $jendela['mulai'] && $jam < $jendela['selesai'];
        $langsungDisetujui = $dalamJendela && $hariWajib;

        $pathFoto = null;
        try {
            $sesiPresensi = DB::transaction(function () use ($user, $data, $sekarang, $terdekat, $langsungDisetujui, &$pathFoto) {
                if ($data['sesi'] === 'pagi') {
                    $pathFoto = $this->simpanFotoSelfie((string) $data['fotoBase64'], $user->id);
                    if ($pathFoto === null) {
                        throw new \RuntimeException('FOTO_TIDAK_VALID');
                    }
                }

                return SesiPresensi::create([
                    'user_id' => $user->id,
                    'tanggal' => $sekarang->toDateString(),
                    'sesi' => $data['sesi'],
                    'waktu_absen' => $sekarang,
                    'lokasi' => $terdekat['pos']['nama'],           // dari server, bukan dari klien
                    'lat' => round((float) $data['lat'], 7),         // koordinat HP saat scan (bahan audit/tinjau)
                    'lng' => round((float) $data['lng'], 7),
                    'jarak_meter' => round($terdekat['jarak'], 2),
                    'akurasi_gps' => round((float) $data['akurasi'], 2),
                    'foto_url' => $pathFoto,                         // path di disk privat (bukan URL publik)
                    'device_id' => $data['deviceId'] ?? null,
                    'status' => 'hadir',
                    'status_persetujuan' => $langsungDisetujui ? 'auto' : 'menunggu',
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'FOTO_TIDAK_VALID') {
                return response()->json(['ok' => false, 'pesan' => 'Foto selfie tidak valid (harus JPEG, maks. ' . config('prakerin.maks_foto_kb') . ' KB).'], 422);
            }
            throw $e;
        } catch (QueryException $e) {
            if ($pathFoto) {
                Storage::disk('local')->delete($pathFoto);
            }
            // 23000 = pelanggaran unique (dua permintaan bersamaan untuk sesi yang sama)
            if ((string) $e->getCode() === '23000') {
                return response()->json(['ok' => false, 'pesan' => 'Presensi sesi ini sudah tercatat.'], 409);
            }
            throw $e;
        }

        return response()->json([
            'ok' => true,
            'sesiPresensiId' => $sesiPresensi->id,
            'nama' => $user->name,
            'statusPersetujuan' => $sesiPresensi->status_persetujuan, // 'auto' | 'menunggu'
            'hariWajib' => $hariWajib,
            'fotoUrl' => $pathFoto ? route('presensi.foto', $sesiPresensi) : null, // popup sesi pagi
            'fotoProfilUrl' => $user->foto_profil_url,                              // popup sesi sore (belum ada fiturnya)
        ]);
    }

    // ============ FOTO SELFIE (disk privat, hanya pemilik atau peninjau) ============
    public function foto(SesiPresensi $sesiPresensi)
    {
        $user = Auth::user();
        abort_unless(
            $sesiPresensi->user_id === $user->id || in_array($user->peran, config('prakerin.peran_peninjau'), true),
            403
        );
        abort_if(blank($sesiPresensi->foto_url) || ! Storage::disk('local')->exists($sesiPresensi->foto_url), 404);

        return Storage::disk('local')->response($sesiPresensi->foto_url);
    }

    // ============ BANTUAN ============
    private function posTerdekat(float $lat, float $lng): array
    {
        $terdekat = null;
        foreach (config('prakerin.pos') as $pos) {
            $jarak = $this->hitungJarakMeter($pos['lat'], $pos['lng'], $lat, $lng);
            if ($terdekat === null || $jarak < $terdekat['jarak']) {
                $terdekat = ['pos' => $pos, 'jarak' => $jarak];
            }
        }
        return $terdekat;
    }

    // Rumus Haversine, hasil dalam meter.
    private function hitungJarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // Mengembalikan path di disk privat, atau null kalau foto tidak valid.
    private function simpanFotoSelfie(string $fotoBase64, int $userId): ?string
    {
        $mentah = preg_replace('/^data:image\/jpeg;base64,/', '', $fotoBase64, 1, $jumlahAwalan);
        if ($jumlahAwalan !== 1) {
            return null;
        }

        $biner = base64_decode($mentah, true);
        if ($biner === false || $biner === '' || strlen($biner) > config('prakerin.maks_foto_kb') * 1024) {
            return null;
        }

        $info = @getimagesizefromstring($biner);
        if (! $info || $info['mime'] !== 'image/jpeg') {
            return null;
        }

        $path = 'selfie/' . $userId . '/' . Str::uuid() . '.jpg';
        Storage::disk('local')->put($path, $biner);

        return $path;
    }
}
