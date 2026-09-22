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
        Schema::create('panen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lahan_id')->constrained('lahan')->onDelete('cascade');
            $table->date('tanggal_panen');
            $table->decimal('jumlah_gabah', 10, 2); // dalam kg
            $table->decimal('harga_gabah_per_kg', 10, 2)->nullable();
            $table->decimal('konversi_beras', 10, 2)->nullable(); // hasil konversi gabah ke beras
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panen');
    }
};
