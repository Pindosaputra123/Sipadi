<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\Lahan;
use App\Models\Petani;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        // ==========================================
        // USER ADMIN
        // ==========================================

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        // ==========================================
        // USER PETUGAS
        // ==========================================

        User::updateOrCreate(
            ['email' => 'petugas@example.com'],
            [
                'name' => 'Petugas',
                'role' => 'petugas',
                'password' => bcrypt('password'),
            ]
        );

        // ==========================================
        // DATA PETANI
        // ==========================================

        $petani1 = Petani::updateOrCreate(
            ['email' => 'petani1@example.com'],
            [
                'nama' => 'Pak Budi',
                'nik' => '3201010101010001',
                'alamat' => 'Desa Balabala, Kecamatan Tahu Isi',
                'telepon' => '0211234567',
                'no_hp' => '081234567890',
                'tanggal_lahir' => '1980-05-12',
                'status' => 'aktif',
                'luas_lahan' => 1.5,
                'komoditas' => 'beras',
                'catatan' => 'Petani padi organik',
            ]
        );

        $petani2 = Petani::updateOrCreate(
            ['email' => 'petani2@example.com'],
            [
                'nama' => 'Bu Sari',
                'nik' => '3201010101010002',
                'alamat' => 'Desa Balabala, Kecamatan Risol',
                'telepon' => '0217654321',
                'no_hp' => '082345678901',
                'tanggal_lahir' => '1985-08-20',
                'status' => 'aktif',
                'luas_lahan' => 2.0,
                'komoditas' => 'beras',
                'catatan' => 'Petani lokal',
            ]
        );

        // ==========================================
        // USER PETANI
        // ==========================================

        User::updateOrCreate(
            ['email' => 'petani1@example.com'],
            [
                'name' => 'Petani1',
                'role' => 'petani',
                'petani_id' => $petani1->id,
                'password' => bcrypt('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'petani2@example.com'],
            [
                'name' => 'Petani2',
                'role' => 'petani',
                'petani_id' => $petani2->id,
                'password' => bcrypt('password'),
            ]
        );

        // ==========================================
        // LAHAN PETANI 1
        // ==========================================

        Lahan::updateOrCreate(
            ['nama_lahan' => 'Lahan Sawah A'],
            [
                'petani_id' => $petani1->id,
                'luas' => 1.2,
                'lokasi' => 'Dusun Tengah',
                'jenis_tanah' => 'sawah',
                'status' => 'aktif',
            ]
        );

        // ==========================================
        // LAHAN PETANI 2
        // ==========================================

        Lahan::updateOrCreate(
            ['nama_lahan' => 'Lahan Sawah B'],
            [
                'petani_id' => $petani2->id,
                'luas' => 2.0,
                'lokasi' => 'Dusun Selatan',
                'jenis_tanah' => 'sawah',
                'status' => 'aktif',
            ]
        );

        // ==========================================
        // GUDANG
        // ==========================================

        Gudang::updateOrCreate(
            ['nama_gudang' => 'Gudang Sentral'],
            [
                'lokasi' => 'Kota A',
                'kapasitas' => 5000,
                'status' => 'aktif',
            ]
        );

        // ==========================================
        // TUJUAN DISTRIBUSI
        // ==========================================

        $this->call(TujuanDistribusiSeeder::class);
    }
}
