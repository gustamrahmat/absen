<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataMagang extends Model
{
    protected $table = 'data_magang';

    protected $fillable = [
        'nomor_induk', 'nama', 'tipe_magang', 'jenis_kelamin',
        'no_ktp', 'no_wa', 'alamat', 'tempat_lahir', 'tanggal_lahir',
        'nis_nim', 'asal_sekolah', 'jurusan', 'departemen_id', 'divisi',
        'uuid', 'user_id', 'ditautkan_pada', 'foto_pas', 'pembimbing',
        'no_wa_pembimbing', 'cv_path', 'surat_permohonan_path',
        'periode_magang', 'status_kehadiran_awal', 'tempat_prakerin',
        'tgl_mulai', 'tgl_selesai', 'keterangan', 'status_magang',
    ];

    protected $casts = [
        'ditautkan_pada' => 'datetime',
        'tanggal_lahir' => 'date',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
