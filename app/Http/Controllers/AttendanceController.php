<?php

namespace App\Http\Controllers;

use App\Models\LogPerubahanStatus;
use App\Models\SesiPresensi;
use App\Models\Tugas;
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
    // =========================================================
    // BANTUAN: CEK HARI KERJA
    // =========================================================

    /**
     * Mengecek apakah tanggal merupakan hari kerja.
     */
    public static function hariKerja(Carbon $tanggal): bool
    {
        return in_array(
            $tanggal->dayOfWeekIso,
            config('prakerin.hari_kerja'),
            true
        ) && !in_array(
            $tanggal->toDateString(),
            config('prakerin.libur'),
            true
        );
    }

    // =========================================================
    // HALAMAN: KALENDER REKAP + SCAN + TUGAS
    // =========================================================

    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            $bulan = Carbon::createFromFormat(
                '!Y-m',
                (string) $request->query(
                    'bulan',
                    now()->format('Y-m')
                )
            ) ?: now();
        } catch (\Throwable $e) {
            $bulan = now();
        }

        $bulan = $bulan->startOfMonth();

        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth();

        // Ambil semua sesi presensi bulan ini.
        $sesiBulanIni = SesiPresensi::with('laporanHarian')->where(
            'user_id',
            $user->id
        )
            ->whereBetween(
                'tanggal',
                [
                    $awal->toDateString(),
                    $akhir->toDateString(),
                ]
            )
            ->get()
            ->groupBy(function ($sesi) {
                return Carbon::parse($sesi->tanggal)
                    ->toDateString();
            });

        // Ambil perubahan status manual HRD.
        $logStatusBulanIni = LogPerubahanStatus::where(
            'user_id',
            $user->id
        )
            ->whereBetween(
                'tanggal',
                [
                    $awal->toDateString(),
                    $akhir->toDateString(),
                ]
            )
            ->get()
            ->keyBy(function ($log) {
                return Carbon::parse($log->tanggal)
                    ->toDateString();
            });

        // Hari pertama peserta benar-benar memiliki presensi.
        $tanggalPertama = SesiPresensi::where(
            'user_id',
            $user->id
        )->min('tanggal');

        $tanggalMulai = $tanggalPertama
            ? Carbon::parse($tanggalPertama)->startOfDay()
            : null;

        $hariDalamBulan = [];

        $hitung = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        for (
            $tgl = $awal->copy();
            $tgl->lte($akhir);
            $tgl->addDay()
        ) {
            $key = $tgl->toDateString();

            $status = $this->tentukanStatusHari(
                $tgl->copy(),
                $sesiBulanIni->get($key, collect()),
                $logStatusBulanIni->get($key),
                $tanggalMulai
            );

            $hariDalamBulan[$key] = $status;

            if (array_key_exists($status['kode'], $hitung)) {
                $hitung[$status['kode']]++;
            }
        }

        // Presensi sore yang belum memiliki laporan harian.
        $laporanTertunda = SesiPresensi::where(
            'user_id',
            $user->id
        )
            ->where('sesi', 'sore')
            ->doesntHave('laporanHarian')
            ->orderByDesc('tanggal')
            ->limit(3)
            ->get();

        // Riwayat tugas.
        $riwayatTugas = Tugas::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->limit(5)
            ->get();

        return view(
            'presensi.index',
            compact(
                'bulan',
                'hariDalamBulan',
                'hitung',
                'laporanTertunda',
                'riwayatTugas'
            )
        );
    }

    // =========================================================
    // MENENTUKAN STATUS HARI
    // =========================================================

    private function tentukanStatusHari(
        Carbon $tanggal,
        Collection $sesiHari,
        ?LogPerubahanStatus $logStatus,
        ?Carbon $tanggalMulai
    ): array {
        // Status manual HRD memiliki prioritas tertinggi.
        if ($logStatus) {
            $tandaManual = match ($logStatus->status_baru) {
                'izin' => 'I',
                'sakit' => 'S',
                'cuti' => 'C',
                'alpha' => '✕',
                default => '',
            };

            return [
                'kode' => $logStatus->status_baru,
                'tanda' => $tandaManual,
            ];
        }

        // Weekend / hari libur bukan alpha.
        if (
            !self::hariKerja($tanggal)
            && $sesiHari->isEmpty()
        ) {
            return [
                'kode' => 'libur',
                'tanda' => '',
            ];
        }

        // Jika presensi ditolak HRD → alpha.
        if (
            $sesiHari->contains(
                'status_persetujuan',
                'ditolak'
            )
        ) {
            return [
                'kode' => 'alpha',
                'tanda' => '✕',
            ];
        }

        $adaPagi = $sesiHari->contains(
            'sesi',
            'pagi'
        );

        $adaSore = $sesiHari->contains(
            'sesi',
            'sore'
        );

        // Ada presensi (satu sesi atau pagi + sore lengkap).
        if ($adaPagi || $adaSore) {
            // Hari dihitung hadir hanya setelah HRD menyetujui
            // (status sesi ATAU laporan harian yang di-ACC admin).
            // Presensi lengkap pun tetap "!" sampai admin menekan ACC.
            if ($this->sudahDisetujui($sesiHari)) {
                return [
                    'kode' => 'hadir',
                    'tanda' => '✓',
                ];
            }

            $jamSoreTutup = $tanggal
                ->copy()
                ->setTime(
                    (int) config(
                        'prakerin.jendela.sore.selesai'
                    ),
                    0
                );

            // Hari ini masih berjalan dan baru satu sesi → masih progres.
            if (
                $tanggal->isToday()
                && now()->lt($jamSoreTutup)
                && !($adaPagi && $adaSore)
            ) {
                return [
                    'kode' => 'progres',
                    'tanda' => '…',
                ];
            }

            return [
                'kode' => 'perlu_acc',
                'tanda' => '!',
            ];
        }

        // Hari ini atau masa depan.
        if (
            $tanggal->isFuture()
            || $tanggal->isToday()
        ) {
            return [
                'kode' => 'kosong',
                'tanda' => '',
            ];
        }

        // Sebelum peserta mulai memiliki data presensi.
        if (
            $tanggalMulai === null
            || $tanggal->lt($tanggalMulai)
        ) {
            return [
                'kode' => 'kosong',
                'tanda' => '',
            ];
        }

        // Tidak ada presensi pada hari kerja yang sudah lewat.
        return [
            'kode' => 'alpha',
            'tanda' => '✕',
        ];
    }

    // =========================================================
    // BANTUAN: SUDAH DISETUJUI HRD?
    // =========================================================

    /**
     * Admin menyetujui lewat status sesi (status_persetujuan = 'disetujui')
     * atau lewat laporan harian (laporan_harian.status_review = 'disetujui').
     * Presensi baru berstatus 'menunggu' sampai admin menekan ACC, jadi
     * keduanya dicek. Data lama berstatus 'auto' tidak dihitung disetujui.
     */
    private function sudahDisetujui(Collection $sesiHari): bool
    {
        return $sesiHari->contains('status_persetujuan', 'disetujui')
            || $sesiHari->contains(
                fn ($sesi) => $sesi->laporanHarian?->status_review === 'disetujui'
            );
    }

    // =========================================================
    // HALAMAN SCAN
    // =========================================================

    public function scan()
    {
        if (!self::hariKerja(now())) {
            return redirect()
                ->route('presensi.index')
                ->with(
                    'info',
                    'Hari ini bukan hari kerja, presensi tidak dibuka.'
                );
        }

        return view(
            'presensi.scan',
            [
                'posisi' => config('prakerin.pos'),
                'jendela' => config('prakerin.jendela'),
            ]
        );
    }

    // =========================================================
    // API: VERIFIKASI KARTU ID / QR
    // =========================================================

    public function verifikasiUuid(Request $request)
    {
        $user = Auth::user();

        $uuid = trim(
            (string) $request->input('uuid')
        );

        if (blank($user->uuid_kartu)) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Akunmu belum memiliki Kartu ID. Hubungi admin/HRD.',
            ]);
        }

        if (
            !hash_equals(
                (string) $user->uuid_kartu,
                $uuid
            )
        ) {
            return response()->json([
                'valid' => false,
                'pesan' => 'Kartu ID ini bukan milik akun yang sedang login.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'nama' => $user->name,
            'nomorInduk' => $user->nomor_induk,
            'divisi' => optional(
                $user->departemen
            )->nama,
        ]);
    }

    // =========================================================
    // API: SIMPAN PRESENSI
    // =========================================================

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
            return response()->json([
                'ok' => false,
                'pesan' => 'Data presensi tidak lengkap atau tidak valid.',
            ], 422);
        }

        $data = $validator->validated();

        $sekarang = now();

        // =====================================================
        // 1. CEK HARI KERJA
        // =====================================================

        if (!self::hariKerja($sekarang)) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Hari ini bukan hari kerja.',
            ], 422);
        }

        // =====================================================
        // 2. CEK KARTU ID
        // =====================================================

        if (
            blank($user->uuid_kartu)
            || !hash_equals(
                (string) $user->uuid_kartu,
                trim($data['uuid'])
            )
        ) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Kartu ID ini bukan milik akun yang sedang login.',
            ], 403);
        }

        // =====================================================
        // 3. CEK AKURASI GPS
        // =====================================================

        if (
            (float) $data['akurasi']
            > (float) config(
                'prakerin.maks_akurasi_meter'
            )
        ) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Akurasi GPS kurang baik. Coba lagi di tempat yang lebih terbuka.',
            ], 422);
        }

        // Cari lokasi terdekat.
        $terdekat = $this->posTerdekat(
            (float) $data['lat'],
            (float) $data['lng']
        );

        if (
            $terdekat['jarak']
            > $terdekat['pos']['radius']
        ) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Anda tidak berada di salah satu zona absen yang terdaftar.',
            ], 422);
        }

        // =====================================================
        // 4. CEK PRESENSI GANDA
        // =====================================================

        $sudahAda = SesiPresensi::with(
            'laporanHarian'
        )
            ->where([
                'user_id' => $user->id,
                'tanggal' => $sekarang->toDateString(),
                'sesi' => $data['sesi'],
            ])
            ->first();

        if ($sudahAda) {
            $respons = [
                'ok' => false,
                'pesan' => 'Presensi sesi '
                    . $data['sesi']
                    . ' hari ini sudah tercatat.',
            ];

            if (
                $data['sesi'] === 'sore'
                && !$sudahAda->laporanHarian
            ) {
                $respons['pesan'] .=
                    ' Laporan harianmu belum diisi, mengalihkan ke formulir laporan.';

                $respons['laporanUrl'] = route(
                    'laporan.form',
                    $sudahAda
                );
            }

            return response()->json(
                $respons,
                409
            );
        }

        // =====================================================
        // 5. CEK JAM SESI
        // =====================================================

        $jam = $sekarang->hour
            + ($sekarang->minute / 60);

        $jendela = config(
            'prakerin.jendela.' . $data['sesi']
        );

        $dalamJendela =
            $jam >= $jendela['mulai']
            && $jam < $jendela['selesai'];

        if (!$dalamJendela) {
            $namaSesi = $data['sesi'] === 'pagi'
                ? 'Presensi Pagi'
                : 'Presensi Sore';

            return response()->json([
                'ok' => false,
                'pesan' => $namaSesi
                    . ' hanya dapat dilakukan pukul '
                    . sprintf(
                        '%02d.00',
                        $jendela['mulai']
                    )
                    . ' - '
                    . sprintf(
                        '%02d.00',
                        $jendela['selesai']
                    )
                    . ' WIB.',
            ], 422);
        }

        // =====================================================
        // 6. SIMPAN DATA
        // =====================================================

        $pathFoto = null;

        try {
            $sesiPresensi = DB::transaction(
                function () use (
                    $user,
                    $data,
                    $sekarang,
                    $terdekat,
                    &$pathFoto
                ) {
                    // Foto hanya wajib untuk presensi pagi.
                    if ($data['sesi'] === 'pagi') {
                        $pathFoto = $this->simpanFotoSelfie(
                            (string) $data['fotoBase64'],
                            $user->id
                        );

                        if ($pathFoto === null) {
                            throw new \RuntimeException(
                                'FOTO_TIDAK_VALID'
                            );
                        }
                    }

                    return SesiPresensi::create([
                        'user_id' => $user->id,

                        'tanggal' => $sekarang->toDateString(),

                        'sesi' => $data['sesi'],

                        'waktu_absen' => $sekarang,

                        // Nama lokasi berasal dari server.
                        'lokasi' => $terdekat['pos']['nama'],

                        // Koordinat HP.
                        'lat' => round(
                            (float) $data['lat'],
                            7
                        ),

                        'lng' => round(
                            (float) $data['lng'],
                            7
                        ),

                        'jarak_meter' => round(
                            $terdekat['jarak'],
                            2
                        ),

                        'akurasi_gps' => round(
                            (float) $data['akurasi'],
                            2
                        ),

                        'foto_url' => $pathFoto,

                        'device_id' =>
                            $data['deviceId'] ?? null,

                        'status' => 'hadir',

                        // Presensi baru SELALU menunggu ACC dari admin/HRD.
                        // Berubah menjadi 'disetujui' hanya saat admin menekan
                        // tombol ACC. Jangan diisi 'auto' di sini.
                        'status_persetujuan' => 'menunggu',
                    ]);
                }
            );
        } catch (\RuntimeException $e) {
            if (
                $e->getMessage()
                === 'FOTO_TIDAK_VALID'
            ) {
                return response()->json([
                    'ok' => false,
                    'pesan' =>
                        'Foto selfie tidak valid '
                        . '(harus JPEG, maks. '
                        . config(
                            'prakerin.maks_foto_kb'
                        )
                        . ' KB).',
                ], 422);
            }

            throw $e;
        } catch (QueryException $e) {
            if ($pathFoto) {
                Storage::disk('local')
                    ->delete($pathFoto);
            }

            // 23000 = duplicate / unique violation.
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'ok' => false,
                    'pesan' =>
                        'Presensi sesi ini sudah tercatat.',
                ], 409);
            }

            throw $e;
        }

        // =====================================================
        // 7. RESPONSE BERHASIL
        // =====================================================

        return response()->json([
            'ok' => true,

            'sesiPresensiId' =>
                $sesiPresensi->id,

            'nama' =>
                $user->name,

            'statusPersetujuan' =>
                $sesiPresensi->status_persetujuan,

            'fotoUrl' =>
                $pathFoto
                    ? route(
                        'presensi.foto',
                        $sesiPresensi
                    )
                    : null,

            'fotoProfilUrl' =>
                $user->foto_profil_url,
        ]);
    }

    // =========================================================
    // FOTO SELFIE
    // =========================================================

    public function foto(SesiPresensi $sesiPresensi)
    {
        $user = Auth::user();

        abort_unless(
            $sesiPresensi->user_id === $user->id ||
            in_array(
                $user->peran,
                config('prakerin.peran_peninjau'),
                true
            ),
            403
        );

        abort_if(
            blank($sesiPresensi->foto_url) ||
            ! Storage::disk('local')->exists($sesiPresensi->foto_url),
            404
        );

        return response()->file(
            Storage::disk('local')->path($sesiPresensi->foto_url)
        );
    }

    // =========================================================
    // BANTUAN: LOKASI TERDEKAT
    // =========================================================

    private function posTerdekat(
        float $lat,
        float $lng
    ): array {
        $terdekat = null;

        foreach (
            config('prakerin.pos') as $pos
        ) {
            $jarak = $this->hitungJarakMeter(
                $pos['lat'],
                $pos['lng'],
                $lat,
                $lng
            );

            if (
                $terdekat === null
                || $jarak < $terdekat['jarak']
            ) {
                $terdekat = [
                    'pos' => $pos,
                    'jarak' => $jarak,
                ];
            }
        }

        return $terdekat;
    }

    // =========================================================
    // BANTUAN: HITUNG JARAK HAVERSINE
    // =========================================================

    private function hitungJarakMeter(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $r = 6371000;

        $dLat = deg2rad(
            $lat2 - $lat1
        );

        $dLng = deg2rad(
            $lng2 - $lng1
        );

        $a =
            sin($dLat / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($dLng / 2) ** 2;

        return $r
            * 2
            * atan2(
                sqrt($a),
                sqrt(1 - $a)
            );
    }

    // =========================================================
    // BANTUAN: SIMPAN FOTO SELFIE
    // =========================================================

    private function simpanFotoSelfie(
        string $fotoBase64,
        int $userId
    ): ?string {
        /*
         * Format yang diharapkan:
         *
         * data:image/jpeg;base64,XXXXXX
         */

        $jumlahAwalan = 0;

        $mentah = preg_replace(
            '/^data:image\/jpeg;base64,/',
            '',
            $fotoBase64,
            1,
            $jumlahAwalan
        );

        if ($jumlahAwalan !== 1 || $mentah === null) {
            return null;
        }

        // Decode Base64.
        $biner = base64_decode(
            $mentah,
            true
        );

        if (
            $biner === false
            || $biner === ''
            || strlen($biner)
                > (
                    (int) config(
                        'prakerin.maks_foto_kb'
                    ) * 1024
                )
        ) {
            return null;
        }

        // Pastikan benar-benar JPEG.
        $info = @getimagesizefromstring(
            $biner
        );

        if (
            !$info
            || ($info['mime'] ?? null)
                !== 'image/jpeg'
        ) {
            return null;
        }

        // Nama file unik.
        $path =
            'selfie/'
            . $userId
            . '/'
            . Str::uuid()
            . '.jpg';

        Storage::disk('local')->put(
            $path,
            $biner
        );

        return $path;
    }
}