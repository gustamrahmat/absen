<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departemen extends Model
{
    protected $table = 'departemen';
    protected $fillable = ['nama', 'kuota'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function dataMagang()
    {
        return $this->hasMany(DataMagang::class);
    }
}
