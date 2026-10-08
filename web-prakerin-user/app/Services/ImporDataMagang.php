<?php

namespace App\Services;

use App\Models\DataMagang;
use App\Models\Departemen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Impor daftar peserta dari Excel (sheet PERTAMA, baris pertama = judul kolom, urutan kolom bebas).
// Bisa dipanggil dari Artisan (prakerin:impor-peserta) maupun dari halaman admin:
//     $hasil = app(ImporDataMagang::class)->jalankan($pathFile, hanyaUji: false);
//
// Aturan:
//  - Idempoten: baris dicocokkan lewat NIS/NIM; menjalankan ulang file yang sama tidak membuat data ganda.
//  - UUID di Excel (kartu lama dari server Node) DIPERTAHANKAN. UUID baru dibuat hanya untuk baris yang kosong.
//  - UUID yang sudah tersimpan di database TIDAK PERNAH ditimpa (kartu bisa sudah tercetak / sudah ditautkan).
//  - Baris bermasalah dilewati dan dilaporkan; baris lain tetap diproses.
class ImporDataMagang
{
    // Nama kolom yang dikenali (huruf besar, spasi dirapatkan). Wajib: nomorInduk dan nama.
    private const KOLOM = [
        'nomorInduk' => ['NIS/NIM', 'NIM/NIS', 'NIM/NISN', 'NISN/NIM', 'NIM', 'NISN', 'NIS', 'NOMOR INDUK'],
        'nama' => ['NAMA', 'NAMA LENGKAP'],
        'asalSekolah' => ['UNIV/SEKOLAH', 'SEKOLAH/UNIV', 'ASAL SEKOLAH', 'SEKOLAH', 'UNIVERSITAS'],
        'jurusan' => ['PRODI', 'JURUSAN', 'PROGRAM STUDI'],
        'departemen' => ['DEPARTEMEN', 'DIVISI'],
        'uuid' => ['UUID'],
    ];

    public function jalankan(string $pathFile, bool $hanyaUji = false): array
    {
        $baris = IOFactory::load($pathFile)->getSheet(0)->toArray(null, true, true, false);
        if (count($baris) < 2) {
            throw new \InvalidArgumentException('File kosong atau hanya berisi judul kolom.');
        }

        $peta = $this->petakanKolom($baris[0]);

        $hasil = [
            'hanyaUji' => $hanyaUji, 'baru' => 0, 'diperbarui' => 0, 'tetap' => 0, 'uuidBaru' => 0,
            'departemenBaru' => [], 'peringatan' => [], 'galat' => [],
        ];
        $sudahDiFile = [];
        $cacheDepartemen = [];

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($baris); $i++) {
                $nomorBaris = $i + 1; // nomor baris seperti terlihat di Excel
                $ambil = fn (string $kunci) => isset($peta[$kunci])
                    ? Str::squish((string) ($baris[$i][$peta[$kunci]] ?? ''))
                    : '';

                $nomorInduk = $ambil('nomorInduk');
                $nama = $ambil('nama');
                $asalSekolah = $ambil('asalSekolah');
                $jurusan = $ambil('jurusan');
                $namaDepartemen = $ambil('departemen');
                $uuidExcel = strtolower($ambil('uuid'));

                if (($nomorInduk . $nama . $asalSekolah . $jurusan . $namaDepartemen . $uuidExcel) === '') {
                    continue; // baris kosong
                }
                if ($nomorInduk === '' || $nama === '') {
                    $hasil['galat'][] = "Baris {$nomorBaris}: NIS/NIM dan NAMA wajib diisi.";
                    continue;
                }
                $kunciFile = strtolower($nomorInduk);
                if (isset($sudahDiFile[$kunciFile])) {
                    $hasil['galat'][] = "Baris {$nomorBaris}: NIS/NIM {$nomorInduk} dobel di file (sudah ada di baris {$sudahDiFile[$kunciFile]}).";
                    continue;
                }
                $sudahDiFile[$kunciFile] = $nomorBaris;
                if ($uuidExcel !== '' && ! Str::isUuid($uuidExcel)) {
                    $hasil['galat'][] = "Baris {$nomorBaris} ({$nomorInduk}): UUID tidak valid.";
                    continue;
                }

                // Departemen dicari/dibuat DI LUAR savepoint baris supaya cache tetap benar kalau baris gagal.
                $departemenId = null;
                if ($namaDepartemen !== '') {
                    $kunciDep = strtolower($namaDepartemen);
                    if (! array_key_exists($kunciDep, $cacheDepartemen)) {
                        $dep = Departemen::firstOrCreate(['nama' => $namaDepartemen]);
                        if ($dep->wasRecentlyCreated) {
                            $hasil['departemenBaru'][] = $dep->nama;
                        }
                        $cacheDepartemen[$kunciDep] = $dep->id;
                    }
                    $departemenId = $cacheDepartemen[$kunciDep];
                }

                try {
                    DB::transaction(function () use (&$hasil, $nomorBaris, $nomorInduk, $nama, $asalSekolah, $jurusan, $departemenId, $uuidExcel) {
                        $rekam = DataMagang::where('nomor_induk', $nomorInduk)->first();
                        $nilai = [
                            'nama' => $nama,
                            'asal_sekolah' => $asalSekolah !== '' ? $asalSekolah : null,
                            'jurusan' => $jurusan !== '' ? $jurusan : null,
                            'departemen_id' => $departemenId,
                        ];

                        if (! $rekam) {
                            $uuidPakai = $uuidExcel !== '' ? $uuidExcel : (string) Str::uuid();
                            $dipakai = DataMagang::where('uuid', $uuidPakai)->exists()
                                || User::where('uuid_kartu', $uuidPakai)->exists();
                            if ($dipakai) {
                                $hasil['galat'][] = "Baris {$nomorBaris} ({$nomorInduk}): UUID sudah dipakai peserta lain.";
                                return;
                            }
                            DataMagang::create($nilai + ['nomor_induk' => $nomorInduk, 'uuid' => $uuidPakai]);
                            $hasil['baru']++;
                            if ($uuidExcel === '') {
                                $hasil['uuidBaru']++;
                            }
                            return;
                        }

                        if ($uuidExcel !== '' && $uuidExcel !== strtolower($rekam->uuid)) {
                            $hasil['peringatan'][] = "Baris {$nomorBaris} ({$nomorInduk}): UUID di Excel berbeda dengan yang tersimpan di database, UUID Excel diabaikan.";
                        }

                        $rekam->fill($nilai);
                        if ($rekam->isDirty()) {
                            $rekam->save();
                            $hasil['diperbarui']++;
                        } else {
                            $hasil['tetap']++;
                        }
                    });
                } catch (\Throwable $e) {
                    $hasil['galat'][] = "Baris {$nomorBaris} ({$nomorInduk}): gagal disimpan ({$e->getMessage()}).";
                }
            }

            $hanyaUji ? DB::rollBack() : DB::commit(); // mode uji: semua dibatalkan, hanya laporan yang dikembalikan
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $hasil;
    }

    // Mencocokkan judul kolom di baris pertama dengan nama yang dikenali -> indeks kolom.
    private function petakanKolom(array $judul): array
    {
        $peta = [];
        foreach ($judul as $indeks => $teks) {
            $bersih = strtoupper(Str::squish(str_replace("\xEF\xBB\xBF", '', (string) $teks)));
            foreach (self::KOLOM as $kunci => $alias) {
                if (! isset($peta[$kunci]) && in_array($bersih, $alias, true)) {
                    $peta[$kunci] = $indeks;
                }
            }
        }

        if (! isset($peta['nomorInduk']) || ! isset($peta['nama'])) {
            $terbaca = implode(', ', array_filter(array_map(fn ($t) => trim((string) $t), $judul)));
            throw new \InvalidArgumentException(
                'Kolom wajib tidak ditemukan. Dibutuhkan kolom NIS/NIM dan NAMA di baris pertama sheet pertama. '
                . 'Judul kolom yang terbaca: ' . ($terbaca ?: '(kosong)')
            );
        }

        return $peta;
    }
}
