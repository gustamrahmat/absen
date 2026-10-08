<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPerubahanStatus extends Model
{
    protected $table = 'log_perubahan_status';

    protected $fillable = ['user_id', 'tanggal', 'status_baru', 'catatan', 'diubah_oleh'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pengubah()
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
