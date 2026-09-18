<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stok_beras';

    protected $fillable = [
        'gudang_id',
        'jenis_transaksi',
        'komoditas',
        'jumlah',
        'keterangan',
        'jumlah_stok',
        'status',
        'batas_minimum',
        'tanggal_update',
        'catatan',
        'foto_bukti',
        'user_id',
        'tujuan_distribusi_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'jumlah_stok' => 'decimal:2',
        'batas_minimum' => 'decimal:2',
        'tanggal_update' => 'datetime',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tujuanDistribusi()
    {
        return $this->belongsTo(TujuanDistribusi::class, 'tujuan_distribusi_id');
    }
}
