<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // some installs have column `tanggal_update` while others have `tanggal`.
        if (Schema::hasTable('stok_beras')) {
            if (Schema::hasColumn('stok_beras', 'tanggal_update')) {
                DB::statement('ALTER TABLE `stok_beras` MODIFY `tanggal_update` DATETIME NULL');
            } elseif (Schema::hasColumn('stok_beras', 'tanggal')) {
                DB::statement('ALTER TABLE `stok_beras` MODIFY `tanggal` DATETIME NULL');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stok_beras')) {
            if (Schema::hasColumn('stok_beras', 'tanggal_update')) {
                DB::statement('ALTER TABLE `stok_beras` MODIFY `tanggal_update` DATE NULL');
            } elseif (Schema::hasColumn('stok_beras', 'tanggal')) {
                DB::statement('ALTER TABLE `stok_beras` MODIFY `tanggal` DATE NULL');
            }
        }
    }
};
