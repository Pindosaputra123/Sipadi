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
        'catatan',
        'no_hp',
        'telepon',
        'luas_lahan',
        'komoditas',
        'email',
        'tanggal_lahir',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'luas_lahan' => 'integer',
    ];

    public function lahan()
    {
        return $this->hasMany(Lahan::class);
    }
}
