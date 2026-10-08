<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nomor_induk',
        'peran',
        'status_akun',
        'departemen_id',
        'uuid_kartu',
        'foto_profil_url',
        'asal_sekolah',
        'jurusan',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Sintaks method casts() ini untuk Laravel 11+. Kalau project rekanmu Laravel 10 ke bawah,
    // ganti jadi property: protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Laravel otomatis Hash::make() saat kolom ini diisi
        ];
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function sesiPresensi()
    {
        return $this->hasMany(SesiPresensi::class);
    }

    public function tugas()
    {
        return $this->hasMany(Tugas::class);
    }

    public function laporanHarian()
    {
        return $this->hasManyThrough(LaporanHarian::class, SesiPresensi::class);
    }
}
