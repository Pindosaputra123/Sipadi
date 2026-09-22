<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('stok_beras') && ! Schema::hasColumn('stok_beras', 'status')) {
            Schema::table('stok_beras', function (Blueprint $table) {
                // enum('aktif','dibatalkan') default 'aktif'
                $table->enum('status', ['aktif', 'dibatalkan'])->default('aktif')->after('jumlah_stok');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('stok_beras') && Schema::hasColumn('stok_beras', 'status')) {
            Schema::table('stok_beras', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
