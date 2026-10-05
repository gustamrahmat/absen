<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'nomor_induk', 'peran', 'status_akun',
        'departemen_id', 'uuid_kartu', 'foto_profil_url', 'asal_sekolah', 'jurusan',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Setiap user baru (registrasi, import Excel, seeder, dll) otomatis
     * mendapat uuid_kartu yang dipakai sebagai isi QR.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid_kartu)) {
                $user->uuid_kartu = (string) Str::uuid();
            }
        });
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function dataMagang()
    {
        return $this->hasOne(DataMagang::class);
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