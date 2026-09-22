<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('alerts')) {
            return;
        }

        Schema::table('alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('alerts', 'catatan')) {
                $table->text('catatan')->nullable()->after('status');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `alerts` MODIFY `status` ENUM('aktif','proses','dalam_penanganan','selesai') NOT NULL DEFAULT 'aktif'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('alerts')) {
            return;
        }

        Schema::table('alerts', function (Blueprint $table) {
            if (Schema::hasColumn('alerts', 'catatan')) {
                $table->dropColumn('catatan');
            }
        });
    }
};
