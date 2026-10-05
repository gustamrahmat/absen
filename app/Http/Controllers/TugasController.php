<?php

namespace App\Http\Controllers;

use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TugasController extends Controller
{
    // ============ UPLOAD TUGAS ============
    // Tugas TIDAK setiap hari -- peserta unggah kapan saja saat mentor memberi tugas.
    // Jenis: jpg, png, pdf, doc, docx. Maksimal 2 MB ('max' di Laravel = KB).
    public function simpan(Request $request)
    {
        $user = Auth::user();

        if (! in_array($user->status_akun, ['diterima', 'aktif'], true)) {
            return redirect()->route('presensi.index')
                ->with('gagal_tugas', 'Upload tugas hanya untuk peserta yang sudah diterima.');
        }

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ], [
            'file.required' => 'Pilih file tugas dulu.',
            'file.uploaded' => 'File gagal diunggah. Pastikan ukurannya tidak lebih dari 2 MB.',
            'file.max' => 'File terlalu besar. Maksimal 2 MB.',
            'file.mimes' => 'Jenis file tidak didukung. Gunakan jpg, png, pdf, doc, atau docx.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('presensi.index')->with('gagal_tugas', $validator->errors()->first());
        }

        $berkas = $request->file('file');
        $ekstensi = strtolower($berkas->getClientOriginalExtension());
        // Nama acak: nama asli peserta TIDAK dipakai di disk (cegah tabrakan nama & karakter aneh).
        $path = $berkas->storeAs('tugas/' . $user->id, Str::uuid() . '.' . $ekstensi, 'local');

        Tugas::create([
            'user_id' => $user->id,
            'nama_file_asli' => Str::limit($berkas->getClientOriginalName(), 200, ''),
            'path_file' => $path,
            'tipe_mime' => (string) $berkas->getMimeType(),
            'ukuran_kb' => (int) ceil($berkas->getSize() / 1024),
        ]);

        return redirect()->route('presensi.index')->with('sukses', 'Tugas berhasil diunggah.');
    }

    // ============ UNDUH TUGAS (hanya pemilik atau peninjau) ============
    public function unduh(Tugas $tugas)
    {
        $user = Auth::user();
        $peninjau = in_array($user->peran, config('prakerin.peran_peninjau'), true);

        abort_unless($tugas->user_id === $user->id || $peninjau, 403);
        abort_unless(Storage::disk('local')->exists($tugas->path_file), 404, 'Berkas tugas tidak ditemukan.');

        if ($peninjau && $tugas->user_id !== $user->id && $tugas->dilihat_admin_at === null) {
            $tugas->forceFill(['dilihat_admin_at' => now()])->save();
        }

        // download() memaksa unduhan (Content-Disposition: attachment), bukan dibuka langsung di peramban.
        return Storage::disk('local')->download($tugas->path_file, $tugas->nama_file_asli);
    }
}
