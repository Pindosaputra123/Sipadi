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
        Schema::table('petani', function (Blueprint $table) {
            if (!Schema::hasColumn('petani', 'nik')) {
                $table->string('nik', 32)->nullable()->after('nama');
            }
            if (!Schema::hasColumn('petani', 'telepon')) {
                $table->string('telepon', 50)->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('petani', 'luas_lahan')) {
                $table->unsignedInteger('luas_lahan')->nullable()->after('telepon');
            }
            if (!Schema::hasColumn('petani', 'komoditas')) {
                $table->string('komoditas')->nullable()->after('luas_lahan');
            }
            if (!Schema::hasColumn('petani', 'catatan')) {
                $table->text('catatan')->nullable()->after('alamat');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petani', function (Blueprint $table) {
            if (Schema::hasColumn('petani', 'catatan')) {
                $table->dropColumn('catatan');
            }
            if (Schema::hasColumn('petani', 'komoditas')) {
                $table->dropColumn('komoditas');
            }
            if (Schema::hasColumn('petani', 'luas_lahan')) {
                $table->dropColumn('luas_lahan');
            }
            if (Schema::hasColumn('petani', 'telepon')) {
                $table->dropColumn('telepon');
            }
            if (Schema::hasColumn('petani', 'nik')) {
                $table->dropColumn('nik');
            }
        });
    }
};
