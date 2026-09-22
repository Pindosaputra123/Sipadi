<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('konfigurasi_harga', function (Blueprint $table) {
            $table->dropColumn(['ongkos_giling', 'rasio_konversi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('konfigurasi_harga', function (Blueprint $table) {
            $table->decimal('ongkos_giling', 15, 2)->default(0);
            $table->decimal('rasio_konversi', 5, 2)->default(61.5);
        });
    }
};
