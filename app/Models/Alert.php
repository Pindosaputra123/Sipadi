<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'alerts';

    protected $fillable = [
        'komoditas',
        'stok_saat_ini',
        'batas_minimum',
        'status',
        'catatan',
        'ditangani_oleh',
    ];

    protected $casts = [
        'stok_saat_ini' => 'integer',
        'batas_minimum' => 'integer',
    ];

    public function petugas()
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }
}
