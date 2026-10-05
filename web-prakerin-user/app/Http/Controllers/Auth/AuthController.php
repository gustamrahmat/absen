<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ==== Halaman ====

    public function tampilLanding()
    {
        return view('auth.landing');
    }

    public function tampilDaftar()
    {
        return view('auth.daftar');
    }

    public function tampilMasuk()
    {
        return view('auth.masuk');
    }

    // ==== Aksi: DAFTAR (Sign Up) ====

    public function daftar(Request $request)
    {
        // NIM/NISN dirapikan dulu (spasi di pinggir dibuang) supaya cek unik & pencocokan data magang konsisten.
        $request->merge(['nomor_induk' => trim((string) $request->input('nomor_induk'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            // max:50 sama dengan kolom data_magang.nomor_induk (tempat NIM/NISN dari Excel dicocokkan).
            'nomor_induk' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9.\-\/]+$/', 'unique:users,nomor_induk'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'nomor_induk.unique' => 'NIM/NISN ini sudah terdaftar. Silakan masuk, atau hubungi admin kalau bukan Anda yang mendaftar.',
            'nomor_induk.regex' => 'NIM/NISN hanya boleh berisi huruf, angka, titik, strip, atau garis miring.',
            'nomor_induk.required' => 'NIM/NISN wajib diisi.',
        ]);

        // Email tidak diminta saat daftar (kolom email sekarang boleh kosong). Login memakai NIM/NISN.
        $user = User::create([
            'name' => $data['name'],
            'nomor_induk' => $data['nomor_induk'],
            'password' => Hash::make($data['password']), // 'password' => 'hashed' di Model juga otomatis hash,
                                                            // Hash::make() di sini supaya eksplisit & aman dari versi Laravel manapun
            'peran' => 'peserta',
            'status_akun' => 'calon',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('presensi.index'))->with('sukses', 'Akun berhasil dibuat!');
    }

    // ==== Aksi: MASUK (Login) ====
    // Identifier boleh NOMOR INDUK (NIM/NISN) atau EMAIL. Peserta baru masuk dengan NIM/NISN;
    // email tetap diterima untuk akun yang punya email (mentor/admin, atau peserta lama).

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($data['identifier']);
        $user = User::where('nomor_induk', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        // Pesan disamakan (tidak bilang "email tidak ditemukan" vs "sandi salah") supaya
        // orang lain tidak bisa menebak email mana saja yang sudah terdaftar.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => 'NIM/NISN atau kata sandi salah.',
            ]);
        }

        Auth::login($user, $request->boolean('ingat')); // true = cookie "remember me" Laravel (persisten)
        $request->session()->regenerate();

        return redirect()->intended(route('presensi.index'));
    }

    // ==== Aksi: KELUAR (Logout) ====

    public function keluar(Request $request)
    {
        // Auth::logout() juga mengganti remember_token, jadi cookie "ingat saya" di semua perangkat tidak berlaku lagi.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Cache-Control: no-store (middleware TanpaCache) mencegah halaman lama tampil lagi lewat tombol back/forward.
        return redirect()->route('login')->with('sukses', 'Anda sudah keluar.');
    }
}
