<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distribusi extends Model
{
    protected $table = 'distribusi';

    protected $fillable = [
        'gudang_id',
        'jumlah_distribusi',
        'tujuan',
        'tanggal_distribusi',
        'catatan',
    ];

    protected $casts = [
        'tanggal_distribusi' => 'date',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
}
