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
        Schema::create('lahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petani_id')->constrained('petani')->onDelete('cascade');
            $table->string('nama_lahan');
            $table->decimal('luas', 8, 2); // dalam hektar
            $table->text('lokasi');
            $table->enum('jenis_tanah', ['sawah', 'ladang', 'kebun'])->default('sawah');
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lahan');
    }
};
