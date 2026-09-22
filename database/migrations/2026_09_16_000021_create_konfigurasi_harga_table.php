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
        Schema::create('konfigurasi_harga', function (Blueprint $table) {
            $table->id();
            $table->decimal('harga_beli_gabah', 15, 2);
            $table->decimal('ongkos_giling', 15, 2);
            $table->decimal('harga_jual_beras', 15, 2);
            $table->decimal('rasio_konversi', 5, 2);
            $table->date('berlaku_mulai');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_harga');
    }
};
