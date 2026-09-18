<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TujuanDistribusi extends Model
{
    protected $table = 'tujuan_distribusi';

    protected $fillable = [
        'nama',
    ];

    public function stok()
    {
        return $this->hasMany(Stok::class, 'tujuan_distribusi_id');
    }
}
