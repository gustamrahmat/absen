<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiPresensi extends Model
{
    protected $table = 'sesi_presensi';

    protected $fillable = [
        'user_id', 'tanggal', 'sesi', 'waktu_absen', 'lokasi', 'lat', 'lng', 'jarak_meter',
        'akurasi_gps', 'foto_url', 'device_id', 'status', 'status_persetujuan',
        'disetujui_oleh', 'catatan_approval',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_absen' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function laporanHarian()
    {
        return $this->hasOne(LaporanHarian::class);
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
