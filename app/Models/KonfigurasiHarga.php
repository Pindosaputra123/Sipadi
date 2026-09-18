<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KonfigurasiHarga extends Model
{
    protected $table = 'konfigurasi_harga';

    protected $fillable = [
        'harga_beli_gabah',
        'ongkos_giling',
        'harga_jual_beras',
        'rasio_konversi',
        'berlaku_mulai',
        'is_active',
    ];

    protected $casts = [
        'berlaku_mulai' => 'date',
        'is_active' => 'boolean',
    ];
}
