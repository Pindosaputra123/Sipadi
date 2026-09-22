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
        if (! Schema::hasTable('stok_beras')) {
            return;
        }

        Schema::table('stok_beras', function (Blueprint $table) {
            if (! Schema::hasColumn('stok_beras', 'tujuan_distribusi_id')) {
                $table->foreignId('tujuan_distribusi_id')
                    ->nullable()
                    ->constrained('tujuan_distribusi')
                    ->nullOnDelete()
                    ->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('stok_beras')) {
            return;
        }

        Schema::table('stok_beras', function (Blueprint $table) {
            if (Schema::hasColumn('stok_beras', 'tujuan_distribusi_id')) {
                $table->dropForeign(['tujuan_distribusi_id']);
                $table->dropColumn('tujuan_distribusi_id');
            }
        });
    }
};
