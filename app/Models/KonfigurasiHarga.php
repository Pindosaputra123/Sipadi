<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KonfigurasiHarga extends Model
{
    protected $table = 'konfigurasi_harga';

    protected $fillable = [
        'harga_beli_gabah',
        'harga_jual_beras',
        'berlaku_mulai',
        'is_active',
    ];

    protected $casts = [
        'harga_beli_gabah' => 'decimal:2',
        'harga_jual_beras' => 'decimal:2',
        'berlaku_mulai' => 'date',
        'is_active' => 'boolean',
    ];
}
