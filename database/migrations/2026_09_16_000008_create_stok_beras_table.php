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
        Schema::create('stok_beras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudang')->onDelete('cascade');
            $table->decimal('jumlah_stok', 10, 2); // dalam kg
            $table->decimal('batas_minimum', 10, 2)->default(1000); // kg
            $table->dateTime('tanggal_update');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_beras');
    }
};
