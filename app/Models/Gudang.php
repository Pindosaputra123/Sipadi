<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    protected $table = 'gudang';

    protected $fillable = [
        'nama_gudang',
        'lokasi',
        'kapasitas',
        'status',
    ];

    public function stok()
    {
        return $this->hasMany(Stok::class);
    }

    public function distribusi()
    {
        return $this->hasMany(Distribusi::class);
    }
}
