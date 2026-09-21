<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\AlertController;
use App\Models\AlertConfiguration;
use App\Models\Gudang;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StokController extends Controller
{
    public function index()
    {
        return Stok::with('gudang')->paginate(15);
    }

    public function show(Stok $stok)
    {
        return $stok->load('gudang');
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/stok/current?komoditas=Beras
    // Ambil stok terkini untuk komoditas tertentu (untuk validasi client-side)
    // ──────────────────────────────────────────────────────────────────────
    public function current(Request $request)
    {
        $komoditas = $request->query('komoditas');
        if (!$komoditas) {
            return response()->json(['error' => 'Parameter komoditas required'], 400);
        }
        
        $latestStok = Stok::where('komoditas', $komoditas)
            ->where('status', 'aktif')
            ->latest('tanggal_update')
            ->latest('id')
            ->first();
        
        if (!$latestStok) {
            // Jika belum ada data, return default values
            return response()->json([
                'jumlah_stok' => 0,
                'batas_minimum' => 500,
                'komoditas' => $komoditas,
            ]);
        }
        
        return response()->json([
            'jumlah_stok' => (float) $latestStok->jumlah_stok,
            'batas_minimum' => (float) $latestStok->batas_minimum,
            'komoditas' => $latestStok->komoditas,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gudang_id'      => 'required|integer|exists:gudang,id',
            'jumlah_stok'    => 'required|numeric|min:0',
            'batas_minimum'  => 'required|numeric|min:0',
            'tanggal_update' => 'required|date',
            'catatan'        => 'nullable|string',
        ]);

        $data['status'] = 'aktif';
        $stok = Stok::create($data);

        if (! empty($stok->komoditas)) {
            $config       = \Illuminate\Support\Facades\Schema::hasTable('alert_configurations')
                ? \App\Models\AlertConfiguration::first()
                : null;
            $batasMinimum = $stok->komoditas === 'Beras'
                ? ($config?->batas_min_beras ?? 400)
                : ($config?->batas_min_gabah ?? 1000);

            AlertController::checkAndCreateAlert($stok->komoditas, (float) $stok->jumlah_stok, (int) $batasMinimum);
        }

        return response()->json($stok, 201);
    }

    public function update(Request $request, Stok $stok)
    {
        $data = $request->validate([
            'gudang_id'      => 'sometimes|required|integer|exists:gudang,id',
            'jumlah_stok'    => 'sometimes|required|numeric|min:0',
            'batas_minimum'  => 'sometimes|required|numeric|min:0',
            'tanggal_update' => 'sometimes|required|date',
            'catatan'        => 'nullable|string',
        ]);

        $stok->update($data);

        if (! empty($stok->komoditas) && isset($stok->jumlah_stok)) {
            $config       = \Illuminate\Support\Facades\Schema::hasTable('alert_configurations')
                ? \App\Models\AlertConfiguration::first()
                : null;
            $batasMinimum = $stok->komoditas === 'Beras'
                ? ($config?->batas_min_beras ?? 400)
                : ($config?->batas_min_gabah ?? 1000);

            AlertController::checkAndCreateAlert($stok->komoditas, (float) $stok->jumlah_stok, (int) $batasMinimum);
        }

        return response()->json($stok);
    }

    public function destroy(Stok $stok)
    {
        $stok->delete();

        return response()->json(null, 204);
    }

    public function monitoring()
    {
        // ✅ PERBAIKI: Ambil data terkini per komoditas berdasarkan tanggal_update (bukan MAX id)
        // Beras terkini
        $stokBeras = Stok::where('komoditas', 'Beras')
            ->where('status', 'aktif')
            ->latest('tanggal_update')
            ->latest('id')
            ->first();

        // Gabah terkini
        $stokGabah = Stok::where('komoditas', 'Gabah')
            ->where('status', 'aktif')
            ->latest('tanggal_update')
            ->latest('id')
            ->first();

        $results = collect();

        if ($stokBeras) {
            $stokBeras->status = $stokBeras->jumlah_stok < $stokBeras->batas_minimum ? 'low' : 'ok';
            $stokBeras->load('gudang');
            $results->push($stokBeras);
        }

        if ($stokGabah) {
            $stokGabah->status = $stokGabah->jumlah_stok < $stokGabah->batas_minimum ? 'low' : 'ok';
            $stokGabah->load('gudang');
            $results->push($stokGabah);
        }

        // Fallback jika tidak ada data per komoditas, ambil per gudang
        if ($results->isEmpty()) {
            $latestIds = Stok::where('status', 'aktif')
                ->latest('tanggal_update')
                ->latest('id')
                ->groupBy('gudang_id')
                ->pluck('id');

            $results = Stok::with('gudang')
                ->whereIn('id', $latestIds)
                ->get()
                ->map(function ($item) {
                    $item->status = $item->jumlah_stok < $item->batas_minimum ? 'low' : 'ok';
                    return $item;
                });
        }

        return response()->json($results->values());
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/stok/summary
    // Ringkasan statistik: saldo per komoditas, masuk/keluar bulan ini
    // ──────────────────────────────────────────────────────────────────────
    public function summary()
    {
        $now        = now();
        $bulanIni   = $now->month;
        $tahunIni   = $now->year;

        // Saldo terkini per komoditas = jumlah_stok dari baris terakhir per komoditas
        // (hanya transaksi dengan status 'aktif')
        $saldoBeras = (float) Stok::where('komoditas', 'Beras')
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_update')
            ->orderByDesc('id')
            ->value('jumlah_stok') ?? 0;

        $saldoGabah = (float) Stok::where('komoditas', 'Gabah')
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_update')
            ->orderByDesc('id')
            ->value('jumlah_stok') ?? 0;

        // Kapasitas dari konfigurasi pengaturan admin
        $alertConfig    = AlertConfiguration::first();
        $kapasitasBeras = (float) ($alertConfig->kapasitas_max_beras ?? 1000);
        $kapasitasGabah = (float) ($alertConfig->kapasitas_max_gabah ?? 2000);

        // Transaksi bulan ini (hanya transaksi aktif)
        $transaksiBase = Stok::where('status', 'aktif')
            ->whereMonth('tanggal_update', $bulanIni)
            ->whereYear('tanggal_update', $tahunIni)
            ->whereNotNull('jenis_transaksi');

        $masukBulanIni = (float) (clone $transaksiBase)
            ->where('jenis_transaksi', 'masuk')
            ->sum('jumlah');

        $keluarBulanIni = (float) (clone $transaksiBase)
            ->where('jenis_transaksi', 'keluar')
            ->sum('jumlah');

        $masukBerasBulanIni = (float) (clone $transaksiBase)
            ->where('jenis_transaksi', 'masuk')
            ->where('komoditas', 'Beras')
            ->sum('jumlah');

        $masukGabahBulanIni = (float) (clone $transaksiBase)
            ->where('jenis_transaksi', 'masuk')
            ->where('komoditas', 'Gabah')
            ->sum('jumlah');

        $keluarBerasBulanIni = (float) (clone $transaksiBase)
            ->where('jenis_transaksi', 'keluar')
            ->where('komoditas', 'Beras')
            ->sum('jumlah');

        $keluarGabahBulanIni = (float) (clone $transaksiBase)
            ->where('jenis_transaksi', 'keluar')
            ->where('komoditas', 'Gabah')
            ->sum('jumlah');

        return response()->json([
            'saldo_beras'            => $saldoBeras,
            'saldo_gabah'            => $saldoGabah,
            'kapasitas_beras'        => $kapasitasBeras,
            'kapasitas_gabah'        => $kapasitasGabah,
            'masuk_bulan_ini'        => $masukBulanIni,
            'keluar_bulan_ini'       => $keluarBulanIni,
            'masuk_beras_bulan_ini'  => $masukBerasBulanIni,
            'masuk_gabah_bulan_ini'  => $masukGabahBulanIni,
            'keluar_beras_bulan_ini' => $keluarBerasBulanIni,
            'keluar_gabah_bulan_ini' => $keluarGabahBulanIni,
            'bulan'                  => $now->translatedFormat('F Y'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/stok/transaksi
    // Daftar mutasi dengan filter: jenis, komoditas, q (search), tanggal
    // ──────────────────────────────────────────────────────────────────────
    public function transaksi(Request $request)
    {
        // Tampilkan SEMUA transaksi (aktif & dibatalkan) agar riwayat lengkap
        // dan petugas bisa mengaktifkan kembali yang dibatalkan
        $query = Stok::with(['gudang', 'user'])
            ->whereNotNull('jenis_transaksi')
            ->latest('tanggal_update');

        if ($request->filled('jenis')) {
            $query->where('jenis_transaksi', strtolower($request->jenis));
        }

        if ($request->filled('komoditas')) {
            $query->where('komoditas', $request->komoditas);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_update', $request->tanggal);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_update', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal_update', '<=', $request->tanggal_akhir);
        }

        // Filter status opsional: ?status=aktif atau ?status=dibatalkan
        // Jika tidak diisi, tampilkan semua
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('komoditas', 'like', "%{$q}%")
                    ->orWhere('keterangan', 'like', "%{$q}%")
                    ->orWhere('catatan', 'like', "%{$q}%")
                    ->orWhereHas('gudang', function ($g) use ($q) {
                        $g->where('nama_gudang', 'like', "%{$q}%")
                          ->orWhere('lokasi', 'like', "%{$q}%");
                    });
            });
        }

        $paginated = $query->paginate(20);

        // Tambahkan field tambahan per item
        $paginated->getCollection()->transform(function ($item) {
            $item->dicatat_oleh  = $item->user?->name ?? 'Admin';
            $item->tanggal_label = $item->tanggal_update
                ? \Carbon\Carbon::parse($item->tanggal_update)->format('Y-m-d H:i')
                : null;
            $item->status = $item->status ?? 'aktif';
            return $item;
        });

        return response()->json($paginated);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/stok/catat
    // Catat transaksi masuk/keluar dan hitung saldo berjalan
    // ──────────────────────────────────────────────────────────────────────
    public function catat(Request $request)
    {
        $data = $request->validate([
            'jenis_transaksi'       => 'required|in:masuk,keluar',
            'komoditas'             => 'required|string',
            'jumlah'                => 'required|numeric|min:0.01',
            'gudang_id'             => 'nullable|integer|exists:gudang,id',
            'keterangan'            => 'nullable|string|max:500',
            'catatan'               => 'nullable|string|max:1000',
            'tanggal'               => 'nullable|date',
            'foto_bukti'            => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // ── Gabah Masuk hanya boleh lewat Pencatatan Panen ──────────────
        // Tolak jika ada yang mencoba catat Gabah Masuk secara manual via API
        if (strtolower($data['komoditas']) === 'gabah' && strtolower($data['jenis_transaksi']) === 'masuk') {
            return response()->json([
                'message' => 'Gabah Masuk tidak dapat dicatat secara manual. Gunakan menu Pencatatan Panen — stok akan diperbarui otomatis.',
            ], 422);
        }
        // ────────────────────────────────────────────────────────────────

        // Tentukan gudang (pakai pertama jika tidak dikirim)
        $gudangId = $data['gudang_id'] ?? Gudang::first()?->id;
        if (! $gudangId) {
            return response()->json(['message' => 'Tidak ada gudang tersedia.'], 422);
        }

        // Hitung saldo terkini untuk komoditas ini (hanya transaksi aktif)
        $saldoSekarang = (float) Stok::where('komoditas', $data['komoditas'])
            ->where('status', 'aktif')
            ->latest('tanggal_update')
            ->latest('id')
            ->value('jumlah_stok') ?? 0;

        $jumlah = (float) $data['jumlah'];
        $saldoBaru = $data['jenis_transaksi'] === 'masuk'
            ? $saldoSekarang + $jumlah
            : $saldoSekarang - $jumlah;

        // Ambil batas minimum dari entri sebelumnya (hanya transaksi aktif)
        $batasMin = (float) Stok::where('komoditas', $data['komoditas'])
            ->where('status', 'aktif')
            ->latest('tanggal_update')
            ->latest('id')
            ->value('batas_minimum') ?? 500;

        $tanggal = isset($data['tanggal'])
            ? \Carbon\Carbon::parse($data['tanggal'])
            : now();

        // Handle file upload untuk foto bukti
        $fotoBuktiPath = null;
        if ($request->hasFile('foto_bukti')) {
            $file = $request->file('foto_bukti');
            $filename = now()->format('Y-m-d-His') . '-' . \Illuminate\Support\Str::random(12) . '.' . $file->getClientOriginalExtension();
            $fotoBuktiPath = $file->storeAs('bukti-distribusi', $filename, 'public');
        }

        $stok = Stok::create([
            'gudang_id'              => $gudangId,
            'jenis_transaksi'        => $data['jenis_transaksi'],
            'komoditas'              => $data['komoditas'],
            'jumlah'                 => $jumlah,
            'keterangan'             => $data['keterangan'] ?? null,
            'catatan'                => $data['catatan'] ?? null,
            'user_id'                => Auth::id(),
            'jumlah_stok'            => $saldoBaru,
            'batas_minimum'          => $batasMin,
            'tanggal_update'         => $tanggal,
            'foto_bukti'             => $fotoBuktiPath,
            'status'                 => 'aktif',
        ]);

        // Cek dan buat alert otomatis jika stok rendah
        $config       = \Illuminate\Support\Facades\Schema::hasTable('alert_configurations')
            ? \App\Models\AlertConfiguration::first()
            : null;
        $batasMinimum = $data['komoditas'] === 'Beras'
            ? ($config?->batas_min_beras ?? 400)
            : ($config?->batas_min_gabah ?? 1000);

        AlertController::checkAndCreateAlert(
            $data['komoditas'],
            $saldoBaru,
            (int) $batasMinimum
        );

        $stok->load(['gudang', 'user', 'tujuanDistribusi']);
        $stok->dicatat_oleh  = $stok->user?->name ?? 'Admin';
        $stok->tanggal_label = $tanggal->format('Y-m-d H:i');
        $stok->tujuan_distribusi_nama = $stok->tujuanDistribusi?->nama;

        return response()->json($stok, 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PATCH /api/stok/{id}/toggle-status
    // Toggle status transaksi antara 'aktif' dan 'dibatalkan',
    // lalu hitung ulang saldo berjalan untuk komoditas terkait.
    // ──────────────────────────────────────────────────────────────────────
    public function toggleStatus(int $id)
    {
        $stok = Stok::findOrFail($id);
        $komoditas = $stok->komoditas;

        $stok->status = ($stok->status === 'aktif') ? 'dibatalkan' : 'aktif';
        $stok->save();

        $this->recalculateSaldo($komoditas);

        // ➕ TAMBAH: Cek dan buat alert setelah hitung ulang saldo
        $saldoTerkini = $this->getCurrentStockValue($komoditas);
        $config = \Illuminate\Support\Facades\Schema::hasTable('alert_configurations')
            ? \App\Models\AlertConfiguration::first()
            : null;
        $batasMinimum = $komoditas === 'Beras'
            ? ($config?->batas_min_beras ?? 400)
            : ($config?->batas_min_gabah ?? 1000);

        AlertController::checkAndCreateAlert($komoditas, $saldoTerkini, (int) $batasMinimum);

        $stok->refresh()->load(['gudang', 'user', 'tujuanDistribusi']);
        $stok->dicatat_oleh  = $stok->user?->name ?? 'Admin';
        $stok->tanggal_label = $stok->tanggal_update
            ? \Carbon\Carbon::parse($stok->tanggal_update)->format('Y-m-d H:i')
            : null;
        $stok->tujuan_distribusi_nama = $stok->tujuanDistribusi?->nama;

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi diperbarui',
            'data'    => $stok,
            'summary' => [
                'stokBeras' => max(0, $this->getCurrentStockValue('Beras')),
                'stokGabah' => max(0, $this->getCurrentStockValue('Gabah')),
            ],
        ]);
    }

    /**
     * Recalculate saldo_setelah / jumlah_stok untuk semua transaksi aktif
     * dari komoditas tertentu, berurutan berdasarkan tanggal_update.
     */
    private function recalculateSaldo(string $komoditas): void
    {
        $saldo = 0;
        $transaksi = Stok::where('komoditas', $komoditas)
            ->where('status', 'aktif')
            ->orderBy('tanggal_update')
            ->orderBy('id')
            ->get();

        foreach ($transaksi as $t) {
            $saldo += $t->jenis_transaksi === 'masuk' ? $t->jumlah : -$t->jumlah;
            $t->jumlah_stok = $saldo;
            if (\Illuminate\Support\Facades\Schema::hasColumn('stok_beras', 'saldo_setelah')) {
                $t->saldo_setelah = $saldo;
            }
            $t->save();
        }
    }

    private function getCurrentStockValue(string $komoditas): float
    {
        return (float) (Stok::where('komoditas', $komoditas)
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_update')
            ->orderByDesc('id')
            ->value('jumlah_stok') ?? 0);
    }
}