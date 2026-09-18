<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lahan extends Model
{
    protected $table = 'lahan';

    protected $fillable = [
        'petani_id',
        'nama_lahan',
        'luas',
        'lokasi',
        'jenis_tanah',
        'status',
    ];

    public function petani()
    {
        return $this->belongsTo(Petani::class);
    }

    public function panen()
    {
        return $this->hasMany(Panen::class);
    }
}
