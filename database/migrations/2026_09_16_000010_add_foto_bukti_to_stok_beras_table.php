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
        if (Schema::hasTable('stok_beras') && ! Schema::hasColumn('stok_beras', 'foto_bukti')) {
            Schema::table('stok_beras', function (Blueprint $table) {
                $table->string('foto_bukti')->nullable()->after('catatan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stok_beras') && Schema::hasColumn('stok_beras', 'foto_bukti')) {
            Schema::table('stok_beras', function (Blueprint $table) {
                $table->dropColumn('foto_bukti');
            });
        }
    }
};
