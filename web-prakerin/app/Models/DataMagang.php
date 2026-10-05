<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataMagang extends Model
{
    protected $table = 'data_magang';

    protected $fillable = [
        'nomor_induk', 'nama', 'asal_sekolah', 'jurusan', 'departemen_id',
        'uuid', 'user_id', 'ditautkan_pada',
    ];

    protected $casts = [
        'ditautkan_pada' => 'datetime',
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
