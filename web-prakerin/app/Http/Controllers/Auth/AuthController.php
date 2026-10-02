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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk ke akun Anda.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
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
    // Identifier boleh EMAIL atau NOMOR INDUK (NIM/NISN), sesuai desain "Alamat Email / NISN / NIM".

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['identifier'])
            ->orWhere('nomor_induk', $data['identifier'])
            ->first();

        // Pesan disamakan (tidak bilang "email tidak ditemukan" vs "sandi salah") supaya
        // orang lain tidak bisa menebak email mana saja yang sudah terdaftar.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => 'Email/NISN/NIM atau kata sandi salah.',
            ]);
        }

        Auth::login($user, $request->boolean('ingat')); // true = cookie "remember me" Laravel (persisten)
        $request->session()->regenerate();

        return redirect()->intended(route('presensi.index'));
    }

    // ==== Aksi: KELUAR (Logout) ====

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
