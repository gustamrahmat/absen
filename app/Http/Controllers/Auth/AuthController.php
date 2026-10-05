<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
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

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        $admin = Admin::where('email', $data['identifier'])->first();

        if ($admin && Hash::check($data['password'], $admin->password)) {

            // Pastikan guard peserta logout
            Auth::guard('web')->logout();

            // Login menggunakan guard ADMIN
            Auth::guard('admin')->login($admin);

            $request->session()->regenerate();

            return redirect()->route('admin.presensi.index');
        }


        /*
        |--------------------------------------------------------------------------
        | PESERTA / MENTOR
        |--------------------------------------------------------------------------
        */

        $user = User::where('email', $data['identifier'])
            ->orWhere('nomor_induk', $data['identifier'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {

            throw ValidationException::withMessages([
                'identifier' => 'Email/NISN/NIM atau kata sandi salah.',
            ]);
        }

        // Pastikan guard admin logout
        Auth::guard('admin')->logout();

        // Login peserta
        Auth::guard('web')->login(
            $user,
            $request->boolean('ingat')
        );

        $request->session()->regenerate();

        return redirect()->intended(route('presensi.index'));
    }

    // ==== Aksi: KELUAR ADMIN ====

    public function keluarAdmin(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ==== Aksi: KELUAR PESERTA ====

    public function keluar(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
