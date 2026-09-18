<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Harga extends Model
{
    protected $table = 'harga';

    protected $fillable = [
        'komoditas',
        'harga_per_kg',
        'tanggal_berlaku',
        'sumber',
    ];

    protected $casts = [
        'tanggal_berlaku' => 'date',
    ];
}
