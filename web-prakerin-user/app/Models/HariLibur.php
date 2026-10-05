<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = ['tanggal', 'nama', 'jenis', 'tanggal_pengganti'];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_pengganti' => 'date',
    ];
}
