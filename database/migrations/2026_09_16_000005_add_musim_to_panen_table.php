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
        if (! Schema::hasTable('panen')) {
            return;
        }

        Schema::table('panen', function (Blueprint $table) {
            if (! Schema::hasColumn('panen', 'musim')) {
                $table->string('musim')->nullable()->after('konversi_beras');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('panen')) {
            return;
        }

        Schema::table('panen', function (Blueprint $table) {
            if (Schema::hasColumn('panen', 'musim')) {
                $table->dropColumn('musim');
            }
        });
    }
};
