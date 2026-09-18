<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $table = 'alerts';

    protected $fillable = [
        'komoditas',
        'stok_saat_ini',
        'batas_minimum',
        'status',
        'ditangani_oleh',
        'catatan',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function handler()
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }
}
