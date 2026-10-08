<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use App\Http\Controllers\Controller;
use App\Models\DataMagang;
use App\Models\Departemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PesertaController extends Controller
{
    public function create()
    {
        return view('admin.peserta.create', [
            'departemen' => Departemen::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $r->merge(['no_ktp' => preg_replace('/\D/', '', (string)$r->no_ktp)]);

        $data = $r->validate([
            'tipe_magang' => 'required|in:smk,kuliah',
            'nama' => 'required|string|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'no_ktp' => 'required|digits:16',
            'no_wa' => 'required|string|max:20',
            'alamat' => 'required|string',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date|before:today',
            'jenis_kelamin' => 'nullable|in:L,P',
            'nis_nim' => 'nullable|string|max:50',
            'asal_sekolah' => 'required|string|max:150',
            'jurusan' => 'required|string|max:150',
            'pembimbing' => 'nullable|string|max:150',
            'no_wa_pembimbing' => 'nullable|string|max:20',
            'departemen_id' => 'required|exists:departemen,id',
            'divisi' => 'nullable|string|max:100',
            'periode_magang' => 'nullable|string|max:100',
            'tempat_prakerin' => 'nullable|string|max:100',
            'tgl_mulai' => 'nullable|date',
            'tgl_selesai' => 'nullable|date|after_or_equal:tgl_mulai',
            'keterangan' => 'nullable|string|max:255',
            'foto_pas' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'cv_path' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'surat_permohonan_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $foto = $r->file('foto_pas')->store('peserta/foto', 'public');
        $cv = $r->hasFile('cv_path') ? $r->file('cv_path')->store('peserta/cv', 'public') : null;
        $surat = $r->hasFile('surat_permohonan_path') ? $r->file('surat_permohonan_path')->store('peserta/surat', 'public') : null;

        try {
            $user = DB::transaction(function () use ($data, $foto, $cv, $surat) {
                $year = date('Y');
                $last = DataMagang::where('nomor_induk', 'like', "MN3-$year-%")
                    ->lockForUpdate()->orderByDesc('nomor_induk')->value('nomor_induk');
                $next = $last ? ((int)substr($last, -3)) + 1 : 1;
                $nomor = sprintf('MN3-%s-%03d', $year, $next);
                $uuid = (string)Str::uuid();

                $user = User::create([
                    'name' => $data['nama'],
                    'email' => $data['email'],
                    'password' => Hash::make($nomor),
                    'nomor_induk' => $nomor,
                    'peran' => 'peserta',
                    'status_akun' => 'aktif',
                    'departemen_id' => $data['departemen_id'],
                    'uuid_kartu' => $uuid,
                    'foto_profil_url' => Storage::disk('public')->url($foto),
                    'asal_sekolah' => $data['asal_sekolah'],
                    'jurusan' => $data['jurusan'],
                ]);

                DataMagang::create([
                    'nomor_induk' => $nomor,
                    'nama' => $data['nama'],
                    'tipe_magang' => $data['tipe_magang'],
                    'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                    'no_ktp' => $data['no_ktp'],
                    'no_wa' => $data['no_wa'],
                    'alamat' => $data['alamat'],
                    'tempat_lahir' => $data['tempat_lahir'],
                    'tanggal_lahir' => $data['tanggal_lahir'],
                    'nis_nim' => $data['nis_nim'] ?? null,
                    'asal_sekolah' => $data['asal_sekolah'],
                    'jurusan' => $data['jurusan'],
                    'departemen_id' => $data['departemen_id'],
                    'divisi' => $data['divisi'] ?? null,
                    'uuid' => $uuid,
                    'user_id' => $user->id,
                    'ditautkan_pada' => now(),
                    'foto_pas' => $foto,
                    'pembimbing' => $data['pembimbing'] ?? null,
                    'no_wa_pembimbing' => $data['no_wa_pembimbing'] ?? null,
                    'cv_path' => $cv,
                    'surat_permohonan_path' => $surat,
                    'periode_magang' => $data['periode_magang'] ?? null,
                    'status_kehadiran_awal' => 'hadir',
                    'tempat_prakerin' => $data['tempat_prakerin'] ?? null,
                    'tgl_mulai' => $data['tgl_mulai'] ?? null,
                    'tgl_selesai' => $data['tgl_selesai'] ?? null,
                    'keterangan' => $data['keterangan'] ?? null,
                    'status_magang' => 'ACTIVE',
                ]);

                return $user;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete([$foto, $cv, $surat]);
            throw $e;
        }

        return redirect()->route('admin.presensi.index')
            ->with('success', 'Peserta berhasil ditambahkan.')
            ->with('qr_peserta_id', $user->id);
    }

    public function qrImage(User $peserta)
    {
        abort_unless($peserta->peran === 'peserta', 404);
        return response($this->buildQr($peserta), 200, ['Content-Type'=>'image/png']);
    }

    public function qrDownload(User $peserta)
    {
        abort_unless($peserta->peran === 'peserta', 404);
        $name = 'QR-'.Str::slug($peserta->name).'-'.$peserta->nomor_induk.'.png';
        return response($this->buildQr($peserta), 200, [
            'Content-Type'=>'image/png',
            'Content-Disposition'=>'attachment; filename="'.$name.'"',
        ]);
    }

    /**
     * Membuat gambar QR (PNG) dari uuid_kartu.
     * Ditulis untuk endroid/qr-code 5.1.0: memakai Builder::create() dengan method berantai (fluent).
     */
    private function buildQr(User $peserta): string
    {
        // Pengaman: kalau uuid_kartu masih kosong, isi dulu.
        if (empty($peserta->uuid_kartu)) {
            $peserta->uuid_kartu = (string) Str::uuid();
            $peserta->save();
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($peserta->uuid_kartu)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();

        return $result->getString();
    }
}