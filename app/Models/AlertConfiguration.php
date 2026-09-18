<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertConfiguration extends Model
{
    use HasFactory;

    protected $table = 'alert_configurations';

    protected $fillable = [
        'batas_min_beras',
        'batas_min_gabah',
        'kapasitas_max_beras',
        'kapasitas_max_gabah',
        'target_pasar',
    ];
}
