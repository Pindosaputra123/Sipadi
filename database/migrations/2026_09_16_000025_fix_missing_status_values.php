<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix missing or NULL status values in stok_beras table
     */
    public function up()
    {
        // Set semua record dengan status NULL atau kosong menjadi 'aktif'
        DB::table('stok_beras')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'aktif']);
    }

    public function down()
    {
        // No rollback needed
    }
};
