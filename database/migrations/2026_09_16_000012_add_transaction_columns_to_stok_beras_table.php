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
            if (! Schema::hasColumn('stok_beras', 'jenis_transaksi')) {
                $table->string('jenis_transaksi')->nullable()->after('gudang_id');
            }

            if (! Schema::hasColumn('stok_beras', 'komoditas')) {
                $table->string('komoditas')->nullable()->after('jenis_transaksi');
            }

            if (! Schema::hasColumn('stok_beras', 'jumlah')) {
                $table->decimal('jumlah', 10, 2)->nullable()->after('komoditas');
            }

            if (! Schema::hasColumn('stok_beras', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('jumlah');
            }

            if (! Schema::hasColumn('stok_beras', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('keterangan');
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
            if (Schema::hasColumn('stok_beras', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('stok_beras', 'keterangan')) {
                $table->dropColumn('keterangan');
            }

            if (Schema::hasColumn('stok_beras', 'jumlah')) {
                $table->dropColumn('jumlah');
            }

            if (Schema::hasColumn('stok_beras', 'komoditas')) {
                $table->dropColumn('komoditas');
            }

            if (Schema::hasColumn('stok_beras', 'jenis_transaksi')) {
                $table->dropColumn('jenis_transaksi');
            }
        });
    }
};
