## Tentang Sipadi
<b>Sipadi</b> adalah platform digital terintegrasi berbasis Website yang dirancang khusus untuk mendukung pengelolaan hasil panen serta distribusi padi secara terdigitalisasi dan terpusat.


### Fitur Sipadi:

#### Admin
-   <b>Dashboard</b><br>
    Ringkasan stok gabah & beras, grafik tren panen 6 bulan, target pasar vs realisasi, alert aktif, harga gabah terkini.
-   <b>Pencatatan Panen</b><br>
    Input hasil gabah + foto bukti wajib, snapshot harga otomatis, gabah langsung masuk stok gudang.
-   <b>Stok Gudang</b><br>
    Transaksi masuk/keluar (foto bukti wajib), saldo real-time, beras input manual setelah giling, riwayat lengkap.
-   <b>Alert Otomatis</b><br>
    Notifikasi banner merah saat stok ≤ batas minimum (konfigurasi terpisah untuk beras & gabah), mark as handled.
-   <b>Manajemen Harga</b><br>
    Konfigurasi harga beli gabah per kg & harga jual beras per kg, riwayat perubahan harga.
-   <b>Data Petani</b><br>
    CRUD data petani mitra, data lahan, kontak, riwayat panen & total penghasilan gabah.
-   <b>Laporan</b><br>
    Rekapitulasi panen & stok per periode — ekspor PDF & Excel.
-   <b>Tujuan Distribusi</b><br>
    Manajemen daftar lokasi pengiriman (MBG, toko mitra).
-   <b>Chatbot Botani</b><br>
    Asisten AI berbasis Groq + Llama 3.3 70B, tanya stok & harga real-time.
-   <b>Pengaturan Sistem</b><br>
    Konfigurasi batas minimum alert, manajemen pengguna (Admin only).

#### Petugas
-   <b>Dashboard</b><br>
    Ringkasan stok gabah & beras, grafik tren panen 6 bulan, target pasar vs realisasi, alert aktif, harga gabah terkini.
-   <b>Pencatatan Panen</b><br>
    Input hasil gabah + foto bukti wajib, snapshot harga otomatis, gabah langsung masuk stok gudang.
-   <b>Stok Gudang</b><br>
    Transaksi masuk/keluar (foto bukti wajib), saldo real-time, beras input manual setelah giling, riwayat lengkap.
-   <b>Alert Otomatis</b><br>
    Notifikasi banner merah saat stok ≤ batas minimum (konfigurasi terpisah untuk beras & gabah), mark as handled.
-   <b>Data Petani</b><br>
    CRUD data petani mitra, data lahan, kontak, riwayat panen & total penghasilan gabah.
-   <b>Laporan</b><br>
    Rekapitulasi panen & stok per periode — ekspor PDF & Excel.
-   <b>Tujuan Distribusi</b><br>
    Manajemen daftar lokasi pengiriman (MBG, toko mitra).
-   <b>Chatbot Botani</b><br>
    Asisten AI berbasis Groq + Llama 3.3 70B, tanya stok & harga real-time.

#### Petani
-   <b>Dashboard</b><br>
    Ringkasan catatan panen, dan harga gabah terkini.
-   <b>Monitoring Panen</b><br>
    Monitoring hasil gabah, dan snapshot harga otomatis.
-   <b>Chatbot Botani</b><br>
    Asisten AI berbasis Groq + Llama 3.3 70B, tanya stok & harga real-time.


## Installation

#### 1. Clone the repository
```sh
https://github.com/Pindosaputra123/Sipadi.git
```
#### 2. Change Directory
```sh
cd Sipadi
```
#### 3. Copy .env
```sh
cp .env.example .env
```
#### 4. Configure .env
```sh
FILESYSTEM_DISK=public
```
#### 5. Install depedencies
```sh
composer install
```
#### 6. Generate Key
```sh
php artisan key:generate
```
#### 7. Run Symlink
```sh
php artisan storage:link
```
#### 8. Migrate database
```sh
php artisan migrate
```
#### 9. Database seeders
```sh
php artisan db:seed
```
#### 10. Run application
```sh
php artisan serve
```

##### <i><b>Note:<br>Admin = email: admin@example.com & password: password <br> Petugas = email: petugas@example.com & password: password <br> Petani = email: petani@example.com & password: password</b></i>
