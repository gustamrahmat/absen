<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = [
        'user_id', 'nama_file_asli', 'path_file', 'tipe_mime', 'ukuran_kb', 'dilihat_admin_at',
    ];

    protected $casts = [
        'dilihat_admin_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
