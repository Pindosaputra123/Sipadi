<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panen extends Model
{
    protected $table = 'panen';

    protected $fillable = [
        'lahan_id',
        'tanggal_panen',
        'jumlah_gabah',
        'harga_gabah_per_kg',
        'konversi_beras',
        'musim',
        'catatan',
        'foto_bukti',
    ];

    protected $casts = [
        'tanggal_panen' => 'date',
        'jumlah_gabah' => 'decimal:2',
        'harga_gabah_per_kg' => 'decimal:2',
        'konversi_beras' => 'decimal:2',
    ];

    public function lahan()
    {
        return $this->belongsTo(Lahan::class);
    }

    public function petani()
    {
        return $this->hasOneThrough(
            Petani::class,
            Lahan::class,
            'id',
            'id',
            'lahan_id',
            'petani_id'
        );
    }
}
