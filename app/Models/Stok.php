<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Stok extends Model
{
    protected $table = 'stok_beras';

    protected $fillable = [
        'gudang_id',
        'jenis_transaksi',
        'komoditas',
        'jumlah',
        'keterangan',
        'catatan',
        'jumlah_stok',
        'status',
        'batas_minimum',
        'tanggal_update',
        'tanggal',
        'user_id',
        'foto_bukti',
    ];

    protected $casts = [
        'tanggal_update' => 'datetime',
        'tanggal' => 'datetime',
    ];

    public function getTanggalAttribute(?string $value): ?string
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        if (! empty($this->attributes['tanggal_update'])) {
            return Carbon::parse($this->attributes['tanggal_update'])->format('Y-m-d H:i:s');
        }

        return null;
    }

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
        return $this->belongsTo(TujuanDistribusi::class);
    }
}
