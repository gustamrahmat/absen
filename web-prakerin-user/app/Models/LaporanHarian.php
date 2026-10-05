<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    protected $table = 'laporan_harian';

    protected $fillable = [
        'sesi_presensi_id', 'laporan', 'status_review',
        'direview_oleh', 'direview_pada',
    ];

    protected $casts = [
        'direview_pada' => 'datetime',
    ];

    public function sesiPresensi()
    {
        return $this->belongsTo(SesiPresensi::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }
}
