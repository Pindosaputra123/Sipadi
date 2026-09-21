<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\AlertController as AdminAlertController;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AlertController extends Controller
{
    public function index()
    {
        return Alert::with('handler')->orderByDesc('created_at')->paginate(50);
    }

    public function show(Alert $alert)
    {
        return $alert->load('handler');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'komoditas' => 'required|string|max:255',
            'stok_saat_ini' => 'required|numeric|min:0',
            'batas_minimum' => 'required|numeric|min:0',
            'status' => 'nullable|in:aktif,proses,selesai',
            'ditangani_oleh' => 'nullable|integer|exists:users,id',
        ]);

        $alert = Alert::create($data);

        return response()->json($alert, 201);
    }

    public function update(Request $request, Alert $alert)
    {
        $data = $request->validate([
            'komoditas' => 'sometimes|required|string|max:255',
            'stok_saat_ini' => 'sometimes|required|numeric|min:0',
            'batas_minimum' => 'sometimes|required|numeric|min:0',
            'status' => 'nullable|in:aktif,proses,dalam_penanganan,selesai',
            'ditangani_oleh' => 'nullable|integer|exists:users,id',
        ]);

        $alert->update($data);

        return response()->json($alert);
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return response()->json(null, 204);
    }

    public function minimum()
    {
        $alerts = Alert::with('handler')
            ->whereColumn('stok_saat_ini', '<', 'batas_minimum')
            ->orWhere('status', 'aktif')
            ->paginate(15);

        return $alerts;
    }

    /**
     * GET /api/alert/konfigurasi
     * Ambil konfigurasi batas minimum dari tabel alert_configurations
     */
    public function getKonfigurasi()
    {
        if (! Schema::hasTable('alert_configurations')) {
            return response()->json([
                'batas_min_beras' => 400,
                'batas_min_gabah' => 1000,
            ]);
        }

        $config = AlertConfiguration::first();

        return response()->json([
            'batas_min_beras' => $config?->batas_min_beras ?? 400,
            'batas_min_gabah' => $config?->batas_min_gabah ?? 1000,
        ]);
    }

    /**
     * PUT /api/alert/konfigurasi
     * Simpan konfigurasi batas minimum ke tabel alert_configurations
     * Sekaligus update batas_minimum di semua baris stok terkini per komoditas
     */
    public function saveKonfigurasi(Request $request)
    {
        $data = $request->validate([
            'batas_min_beras' => 'required|numeric|min:0',
            'batas_min_gabah' => 'required|numeric|min:0',
        ]);

        if (! Schema::hasTable('alert_configurations')) {
            return response()->json(['message' => 'Tabel konfigurasi belum ada. Jalankan migrate.'], 500);
        }

        // Simpan ke tabel alert_configurations
        $config = AlertConfiguration::firstOrNew();
        $config->fill([
            'batas_min_beras' => $data['batas_min_beras'],
            'batas_min_gabah' => $data['batas_min_gabah'],
        ]);
        $config->save();

        // Update batas_minimum pada stok terkini per komoditas agar monitoring konsisten
        \App\Models\Stok::where('komoditas', 'Beras')
            ->update(['batas_minimum' => $data['batas_min_beras']]);

        \App\Models\Stok::where('komoditas', 'Gabah')
            ->update(['batas_minimum' => $data['batas_min_gabah']]);

        // Update batas_minimum pada alerts yang ada agar validasi Flutter konsisten
        Alert::where('komoditas', 'Beras')
            ->update(['batas_minimum' => $data['batas_min_beras']]);

        Alert::where('komoditas', 'Gabah')
            ->update(['batas_minimum' => $data['batas_min_gabah']]);

        // ➕ TAMBAH: Cek dan buat alert untuk kedua komoditas dengan konfigurasi baru
        // Ambil stok terkini per komoditas
        $stokBeras = (float) (\App\Models\Stok::where('komoditas', 'Beras')
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_update')
            ->orderByDesc('id')
            ->value('jumlah_stok') ?? 0);

        $stokGabah = (float) (\App\Models\Stok::where('komoditas', 'Gabah')
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_update')
            ->orderByDesc('id')
            ->value('jumlah_stok') ?? 0);

        // Trigger alert check dengan konfigurasi baru
        AdminAlertController::checkAndCreateAlert('Beras', $stokBeras, (int) $data['batas_min_beras']);
        AdminAlertController::checkAndCreateAlert('Gabah', $stokGabah, (int) $data['batas_min_gabah']);

        return response()->json([
            'message' => 'Konfigurasi berhasil disimpan & alert di-perbarui',
            'batas_min_beras' => $config->batas_min_beras,
            'batas_min_gabah' => $config->batas_min_gabah,
        ]);
    }
}