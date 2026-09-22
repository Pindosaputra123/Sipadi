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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('komoditas');
            $table->integer('stok_saat_ini');
            $table->integer('batas_minimum');
            $table->enum('status', ['aktif', 'proses', 'dalam_penanganan', 'selesai'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
