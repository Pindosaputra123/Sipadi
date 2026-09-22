<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_configurations', function (Blueprint $table) {
            $table->integer('kapasitas_max_beras')->default(1000)->after('batas_min_gabah');
            $table->integer('kapasitas_max_gabah')->default(2000)->after('kapasitas_max_beras');
            $table->integer('target_pasar')->default(9000)->after('kapasitas_max_gabah');
        });
    }

    public function down(): void
    {
        Schema::table('alert_configurations', function (Blueprint $table) {
            $table->dropColumn(['kapasitas_max_beras', 'kapasitas_max_gabah', 'target_pasar']);
        });
    }
};
