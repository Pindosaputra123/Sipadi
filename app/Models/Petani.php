<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Petani extends Model
{
    protected $table = 'petani';

    protected $fillable = [
        'nama',
        'nik',
        'alamat',
        'telepon',
        'no_hp',
        'email',
        'tanggal_lahir',
        'status',
        'luas_lahan',
        'komoditas',
        'catatan',
    ];

    public function lahan()
    {
        return $this->hasMany(Lahan::class);
    }
}
