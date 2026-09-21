<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Stok;
use App\Models\AlertConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $config = Schema::hasTable('alert_configurations') ? AlertConfiguration::first() : null;
        $batasMinBeras = $config?->batas_min_beras ?? 400;
        $batasMinGabah = $config?->batas_min_gabah ?? 1000;

        $stokBeras = $this->getCurrentStock('Beras');
        $stokGabah = $this->getCurrentStock('Gabah');

        // Pastikan alert dibuat ketika stok saat ini sudah di bawah batas minimum
        self::checkAndCreateAlert('Beras', $stokBeras, $batasMinBeras);
        self::checkAndCreateAlert('Gabah', $stokGabah, $batasMinGabah);

        $alertAktif = Alert::where('status', 'aktif')->count();
        $alertProses = Alert::whereIn('status', ['proses', 'dalam_penanganan'])->count();
        $alertSelesai = Alert::where('status', 'selesai')->count();

        $selectedStatus = $request->query('status', '');
        $alertListQuery = Alert::with('handler')->orderByDesc('created_at');

        if ($selectedStatus === 'aktif') {
            $alertListQuery->where('status', 'aktif');
        } elseif ($selectedStatus === 'dalam_penanganan') {
            $alertListQuery->whereIn('status', ['proses', 'dalam_penanganan']);
        } elseif ($selectedStatus === 'selesai') {
            $alertListQuery->where('status', 'selesai');
        }

        $alertList = $alertListQuery->get();

        $stokCards = [
            [
                'komoditas' => 'Beras',
                'stok' => $stokBeras,
                'batas' => $batasMinBeras,
                'kapasitas' => 10000,
            ],
            [
                'komoditas' => 'Gabah',
                'stok' => $stokGabah,
                'batas' => $batasMinGabah,
                'kapasitas' => 5000,
            ],
        ];

        return view('admin.alert.index', compact(
            'stokCards',
            'alertAktif',
            'alertProses',
            'alertSelesai',
            'alertList',
            'selectedStatus'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'komoditas' => 'required|string|max:255',
            'stok_saat_ini' => 'required|numeric|min:0',
            'batas_minimum' => 'required|numeric|min:0',
            'status' => 'required|in:aktif,dalam_penanganan,selesai,proses',
            'ditangani_oleh' => 'nullable|integer|exists:users,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        $data['status'] = $this->normalizeStatus($data['status']);
        Alert::create($data);

        return redirect()->back()->with('success', 'Alert stok berhasil dibuat.');
    }

    public function show($id)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function edit($id)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function tangani(Request $request, Alert $alert)
{
    $newStatus = $request->input('status');

    if (! $newStatus) {
        $newStatus = $this->nextStatus($alert->status);
    }

    $newStatus = $this->normalizeStatus($newStatus);

    if ($newStatus === 'selesai') {
    $stokTerkini = $this->getCurrentStock($alert->komoditas);

    if ($stokTerkini < $alert->batas_minimum) {
        $msg = "Stok {$alert->komoditas} masih {$stokTerkini} kg, di bawah batas minimum {$alert->batas_minimum} kg. Tambahkan stok terlebih dahulu.";
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        return redirect()->back()->with('warning', $msg);
    }
}

    if ($newStatus !== 'aktif' && ! $alert->ditangani_oleh) {
        $alert->ditangani_oleh = Auth::id();
    }

    $alert->status = $newStatus;
    $alert->save();

    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Alert berhasil diperbarui',
            'status' => $newStatus,
            'alert_id' => $alert->id,
        ]);
    }

    return redirect()->back()->with('success', 'Alert berhasil diperbarui');
}

    public function update(Request $request, Alert $alert)
    {
        $data = $request->validate([
            'komoditas' => 'sometimes|required|string|max:255',
            'stok_saat_ini' => 'sometimes|required|numeric|min:0',
            'batas_minimum' => 'sometimes|required|numeric|min:0',
            'status' => 'nullable|in:aktif,dalam_penanganan,selesai,proses',
            'ditangani_oleh' => 'nullable|integer|exists:users,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }

        $alert->update($data);

        return redirect()->back()->with('success', 'Alert stok berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $alert = Alert::findOrFail($id);
        $alert->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Alert berhasil dihapus']);
        }

        return redirect()->back()->with('success', 'Alert berhasil dihapus.');
    }

    public function destroyAll(Request $request)
    {
        $status = $request->input('status', 'selesai');
        $deleted = Alert::where('status', $status)->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "{$deleted} alert berhasil dihapus"]);
        }

        return redirect()->back()->with('success', "{$deleted} alert berhasil dihapus.");
    }

    public function konfigurasi(Request $request)
    {
        if (! Schema::hasTable('alert_configurations')) {
            return redirect()->back()->withErrors([
                'config' => 'Tabel konfigurasi alert belum ada. Jalankan php artisan migrate terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'batas_min_beras' => ['required', 'integer', 'min:0'],
            'batas_min_gabah' => ['required', 'integer', 'min:0'],
        ]);

        $config = AlertConfiguration::firstOrNew();
        $config->fill($validated);
        $config->save();

        return redirect()->back()->with('success', 'Konfigurasi alert berhasil disimpan');
    }



    public function selesai(Request $request, Alert $alert)
    {
        return $this->tangani($request->merge(['status' => 'selesai']), $alert);
    }

    public static function checkAndCreateAlert(string $komoditas, float $stokSaatIni, int $batasMinimum): bool
    {
        if ($stokSaatIni >= $batasMinimum) {
            return false;
        }

        $existing = Alert::where('komoditas', $komoditas)
            ->whereIn('status', ['aktif', 'proses', 'dalam_penanganan'])
            ->exists();

        if ($existing) {
            return false;
        }

        Alert::create([
            'komoditas' => $komoditas,
            'stok_saat_ini' => $stokSaatIni,
            'batas_minimum' => $batasMinimum,
            'status' => 'aktif',
            'catatan' => 'Stok turun di bawah batas minimum',
        ]);

        return true;
    }

    private function getCurrentStock(string $komoditas): float
    {
        $query = Stok::where('komoditas', $komoditas);

        if (Schema::hasColumn('stok_beras', 'status')) {
            $query->where('status', 'aktif');
        }

        if (Schema::hasColumn('stok_beras', 'tanggal_update')) {
            $query->orderByDesc('tanggal_update');
        } elseif (Schema::hasColumn('stok_beras', 'tanggal')) {
            $query->orderByDesc('tanggal');
        } else {
            $query->orderByDesc('created_at');
        }

        return (float) ($query->value('jumlah_stok') ?: 0);
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'proses', 'dalam_penanganan' => 'dalam_penanganan',
            'aktif' => 'aktif',
            'selesai' => 'selesai',
            default => 'aktif',
        };
    }

    private function nextStatus(string $currentStatus): string
    {
        $normalized = $this->normalizeStatus($currentStatus);

        return match ($normalized) {
            'aktif' => 'dalam_penanganan',
            'dalam_penanganan' => 'selesai',
            default => $normalized,
        };
    }
}

