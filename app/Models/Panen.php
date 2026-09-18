<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Lahan;
use App\Models\Petani;

class Panen extends Model
{
    protected $table = 'panen';

    protected $fillable = [
        'lahan_id',
        'tanggal_panen',
        'jumlah_gabah',
        'harga_gabah_per_kg',
        'konversi_beras',  // Menyimpan HASIL beras dalam kg (bukan persentase)
        'musim',
        'catatan',
        'foto_bukti',
    ];

    protected $casts = [
        'tanggal_panen'      => 'date:Y-m-d',  // Pastikan format tanggal bersih (tanpa jam)
        'jumlah_gabah'       => 'float',
        'harga_gabah_per_kg' => 'float',
        'konversi_beras'     => 'float',
    ];

    // Append musim ke response API supaya Flutter bisa baca
    protected $appends = [];

    public function lahan()
    {
        return $this->belongsTo(Lahan::class);
    }

    public function petani()
    {
        return $this->hasOneThrough(
            Petani::class,
            Lahan::class,
            'id',        // Foreign key on Lahan table
            'id',        // Foreign key on Petani table
            'lahan_id',  // Local key on Panen table
            'petani_id'  // Local key on Lahan table
        );
    }

    // Alias getter: tonase_gabah → jumlah_gabah
    public function getTonaseGabahAttribute()
    {
        return $this->jumlah_gabah;
    }

    // konversi_beras sekarang langsung menyimpan hasil dalam kg,
    // BUKAN persentase. Getter ini hanya untuk web view yang perlu nilai beras.
    public function getBerasDihasilkanAttribute()
    {
        return $this->konversi_beras ?? 0;
    }
}
