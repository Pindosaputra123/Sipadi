<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Distribusi;
use App\Models\Gudang;
use App\Models\Harga;
use App\Models\Lahan;
use App\Models\Panen;
use App\Models\Petani;
use App\Models\Stok;
use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TujuanDistribusiSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas@example.com'],
            [
                'name' => 'Petugas',
                'role' => 'petugas',
                'password' => bcrypt('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'petani1@example.com'],
            [
                'name' => 'Petani1',
                'role' => 'petani',
                'password' => bcrypt('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'petani2@example.com'],
            [
                'name' => 'Petani2',
                'role' => 'petani',
                'password' => bcrypt('password'),
            ]
        );

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

        $lahan1 = Lahan::updateOrCreate(
            ['nama_lahan' => 'Lahan Sawah A'],
            [
                'petani_id' => $petani1->id,
                'luas' => 1.2,
                'lokasi' => 'Dusun Tengah',
                'jenis_tanah' => 'sawah',
                'status' => 'aktif',
            ]
        );

        $lahan2 = Lahan::updateOrCreate(
            ['nama_lahan' => 'Lahan Sawah B'],
            [
                'petani_id' => $petani2->id,
                'luas' => 2.0,
                'lokasi' => 'Dusun Selatan',
                'jenis_tanah' => 'sawah',
                'status' => 'aktif',
            ]
        );

        $gudang1 = Gudang::updateOrCreate(
            ['nama_gudang' => 'Gudang Sentral'],
            [
                'lokasi' => 'Kota A',
                'kapasitas' => 5000,
                'status' => 'aktif',
            ]
        );

        // Seed tujuan distribusi (dropdown options)
        $this->call(TujuanDistribusiSeeder::class);
    }
}
