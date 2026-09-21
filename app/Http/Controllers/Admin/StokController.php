<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertConfiguration;
use App\Models\Stok;
use App\Models\TujuanDistribusi;
use App\Models\Petani;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $dateColumn = Schema::hasColumn('stok_beras', 'tanggal_update')
            ? 'tanggal_update'
            : (Schema::hasColumn('stok_beras', 'tanggal') ? 'tanggal' : 'created_at');

        $query = Stok::with('user')->whereNotNull('jenis_transaksi');

        if ($request->filled('tanggal')) {
            $parts = explode('-', $request->tanggal);
            if (count($parts) == 2) {
                $query->whereYear($dateColumn, $parts[0])
                      ->whereMonth($dateColumn, $parts[1]);
            }
        }
        if ($request->filled('jenis')) {
            $query->where('jenis_transaksi', $request->jenis);
        }
        if ($request->filled('komoditas')) {
            $query->where('komoditas', $request->komoditas);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('keterangan', 'like', '%' . $request->q . '%')
                  ->orWhere('catatan', 'like', '%' . $request->q . '%');
            });
        }

        $transactions = $query->orderByDesc($dateColumn)->paginate(10)->withQueryString();

        $currentMonth = now()->month;
        $currentYear = now()->year;

        if ($request->filled('tanggal')) {
            $parts = explode('-', $request->tanggal);
            if (count($parts) == 2) {
                $currentYear = $parts[0];
                $currentMonth = $parts[1];
            }
        }

        $stokBeras = $this->getCurrentStock('Beras');
        $stokGabah = $this->getCurrentStock('Gabah');

        $masukBerasBulanIni = Stok::where('komoditas', 'Beras')
            ->where('status', 'aktif')
            ->where('jenis_transaksi', 'masuk')
            ->whereYear($dateColumn, $currentYear)
            ->whereMonth($dateColumn, $currentMonth)
            ->sum('jumlah');

        $masukGabahBulanIni = Stok::where('komoditas', 'Gabah')
            ->where('status', 'aktif')
            ->where('jenis_transaksi', 'masuk')
            ->whereYear($dateColumn, $currentYear)
            ->whereMonth($dateColumn, $currentMonth)
            ->sum('jumlah');

        $keluarBerasBulanIni = Stok::where('komoditas', 'Beras')
            ->where('status', 'aktif')
            ->where('jenis_transaksi', 'keluar')
            ->whereYear($dateColumn, $currentYear)
            ->whereMonth($dateColumn, $currentMonth)
            ->sum('jumlah');

        $keluarGabahBulanIni = Stok::where('komoditas', 'Gabah')
            ->where('status', 'aktif')
            ->where('jenis_transaksi', 'keluar')
            ->whereYear($dateColumn, $currentYear)
            ->whereMonth($dateColumn, $currentMonth)
            ->sum('jumlah');

        $alertConfig    = AlertConfiguration::first();
        $kapasitasBeras = $alertConfig->kapasitas_max_beras ?? 1000;
        $kapasitasGabah = $alertConfig->kapasitas_max_gabah ?? 2000;

        return view('admin.stok.index', [
            'transaksis' => $transactions,
            'stokBeras' => max(0, $stokBeras),
            'stokGabah' => max(0, $stokGabah),
            'masukBerasBulanIni' => $masukBerasBulanIni,
            'keluarBerasBulanIni' => $keluarBerasBulanIni,
            'masukGabahBulanIni' => $masukGabahBulanIni,
            'keluarGabahBulanIni' => $keluarGabahBulanIni,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'kapasitasBeras' => $kapasitasBeras,
            'kapasitasGabah' => $kapasitasGabah,
            'tujuans' => TujuanDistribusi::orderBy('nama')->get(),
            'petanis' => Petani::orderBy('nama')->get(),
        ]);
    }

    private function getCurrentStock(string $komoditas): float
    {
        $dateColumn = Schema::hasColumn('stok_beras', 'tanggal_update')
            ? 'tanggal_update'
            : (Schema::hasColumn('stok_beras', 'tanggal') ? 'tanggal' : 'created_at');

        $query = Stok::where('komoditas', $komoditas)
            ->where('status', 'aktif');

        if ($dateColumn) {
            $query->orderByDesc($dateColumn);
        }
        $query->orderByDesc('id');

        return (float) ($query->value('jumlah_stok') ?: 0);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis' => 'required|in:masuk,keluar',
            'komoditas' => 'required|string',
            'jumlah' => 'required|numeric|min:0',
            // allow nullable so we can auto-fill with current datetime when empty
            'tanggal' => 'nullable|date',
            'tujuan_distribusi' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'catatan' => 'nullable|string',
            'foto_bukti' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Gabah Masuk hanya boleh lewat Pencatatan Panen — tolak jika ada yang coba masuk manual
        if ($data['komoditas'] === 'Gabah' && $data['jenis'] === 'masuk') {
            return redirect()->back()
                ->withErrors(['jenis' => 'Gabah Masuk tidak bisa dicatat manual. Gunakan menu Pencatatan Panen.'])
                ->withInput();
        }

        // Normalize datetime-local (2026-05-21T05:40) → 'Y-m-d H:i:s'
        if (! empty($data['tanggal'])) {
            $tanggal = str_replace('T', ' ', $data['tanggal']);
            // append seconds if missing
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $tanggal)) {
                $tanggal .= ':00';
            }
        } else {
            // auto-fill current datetime when user didn't provide one
            $tanggal = now()->format('Y-m-d H:i:s');
        }

        if ($data['komoditas'] === 'Beras' && $data['jenis'] === 'masuk') {
            $stokGabah = $this->getCurrentStock('Gabah');
            if ($stokGabah <= 0) {
                return redirect()->back()->withErrors(['komoditas' => 'Beras masuk hanya bisa dicatat jika stok gabah lebih dari 0 (harus ada gabah yang digiling).'])->withInput();
            }
        }

        // Calculate running stock using the canonical getCurrentStock helper
        // so masuk and keluar always compute from the same consistent saldo.
        $previousStock = $this->getCurrentStock($data['komoditas']);
        $jumlahStok = $data['jenis'] === 'keluar'
            ? ($previousStock - $data['jumlah'])
            : ($previousStock + $data['jumlah']);

        // Build payload with safe defaults
        $payload = [
            'gudang_id' => 1,
            'jenis_transaksi' => $data['jenis'],
            'komoditas' => $data['komoditas'],
            'jumlah' => $data['jumlah'],
            'jumlah_stok' => $jumlahStok,
            'keterangan' => trim(($data['tujuan_distribusi'] ?? '') . ' ' . ($data['keterangan'] ?? '')) ?: null,
            'catatan' => $data['catatan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('stok_beras', 'user_id')) {
            $payload['user_id'] = Auth::id();
        }

        // Store datetime to whichever column exists in this install
        if (Schema::hasColumn('stok_beras', 'tanggal_update')) {
            $payload['tanggal_update'] = $tanggal;
        } elseif (Schema::hasColumn('stok_beras', 'tanggal')) {
            $payload['tanggal'] = $tanggal;
        }

        // Handle uploaded foto bukti for distribusi (keluar)
        if ($request->hasFile('foto_bukti')) {
            $file = $request->file('foto_bukti');
            $ext = $file->getClientOriginalExtension();
            $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            // store in storage/app/public/bukti-distribusi
$path = $file->storeAs('bukti-distribusi', $filename, 'public');            // save relative path without 'public/' prefix so asset('storage/...') works
            $payload['foto_bukti'] = 'bukti-distribusi/' . $filename;
        }

        DB::table('stok_beras')->insert($payload);

        if ($data['jenis'] === 'keluar') {
            $config = Schema::hasTable('alert_configurations') ? AlertConfiguration::first() : null;
            $batasMinimum = $data['komoditas'] === 'Beras'
                ? ($config?->batas_min_beras ?? 400)
                : ($config?->batas_min_gabah ?? 1000);

            AlertController::checkAndCreateAlert($data['komoditas'], $jumlahStok, $batasMinimum);
        }

        return redirect()->back()->with('success', 'Transaksi stok tersimpan.');
    }

    /**
     * Toggle status antara 'aktif' dan 'dibatalkan' untuk sebuah transaksi stok.
     * Recalculate saldo setelah update.
     */
    public function toggleStatus(int $id)
    {
        $stok = Stok::findOrFail($id);

        $stok->status = ($stok->status === 'aktif') ? 'dibatalkan' : 'aktif';
        $stok->save();

        // Recalculate saldo for this komoditas
        $this->recalculateSaldo($stok->komoditas);

        // also include updated summary so other pages can update without reload
        $stokBeras = $this->getCurrentStock('Beras');
        $stokGabah = $this->getCurrentStock('Gabah');

        return response()->json([
            'success' => true,
            'status' => $stok->status,
            'message' => 'Status transaksi diperbarui',
            'summary' => [
                'stokBeras' => max(0, $stokBeras),
                'stokGabah' => max(0, $stokGabah),
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $stok = Stok::findOrFail($id);
        $komoditas = $stok->komoditas;
        
        // Hapus foto bukti jika ada
        if ($stok->foto_bukti && \Illuminate\Support\Facades\Storage::disk('public')->exists($stok->foto_bukti)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($stok->foto_bukti);
        }
        
        $stok->delete();

        // Recalculate saldo
        $this->recalculateSaldo($komoditas);

        return redirect()->route('admin.stok.index')
            ->with('success', 'Transaksi stok berhasil dihapus permanen.');
    }

    /**
     * Return a small JSON summary of current stocks for dashboard updates.
     */
    public function summary()
    {
        $stokBeras = $this->getCurrentStock('Beras');
        $stokGabah = $this->getCurrentStock('Gabah');

        return response()->json([
            'stokBeras' => max(0, $stokBeras),
            'stokGabah' => max(0, $stokGabah),
        ]);
    }

    /**
     * Recalculate saldo_setelah untuk semua transaksi aktif dari komoditas tertentu.
     */
    public function recalculateSaldo(string $komoditas)
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
            if (Schema::hasColumn('stok_beras', 'saldo_setelah')) {
                $t->saldo_setelah = $saldo;
            }
            $t->save();
        }
    }

    public function edit(int $id)
    {
        $stok = Stok::with('user')->findOrFail($id);
        $tujuans = TujuanDistribusi::orderBy('nama')->get();

        return view('admin.stok.edit', compact('stok', 'tujuans'));
    }

    public function update(Request $request, int $id)
    {
        $stok = Stok::findOrFail($id);

        $data = $request->validate([
            'jenis_transaksi' => 'required|in:masuk,keluar',
            'komoditas'       => 'required|in:Beras,Gabah',
            'jumlah'          => 'required|numeric|min:0.01',
            'keterangan'      => 'nullable|string|max:500',
            'catatan'         => 'nullable|string|max:1000',
            'tanggal_update'  => 'required|date',
            'foto_bukti'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Handle upload foto baru jika ada
        if ($request->hasFile('foto_bukti')) {
            $file = $request->file('foto_bukti');
            $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $file->getClientOriginalExtension();
            $data['foto_bukti'] = $file->storeAs('bukti-distribusi', $filename, 'public');
        } else {
            unset($data['foto_bukti']);
        }

        $stok->update($data);

        // Normalize tanggal_update format (datetime-local sends 'Y-m-dTH:i')
        if (!empty($data['tanggal_update'])) {
            $normalized = str_replace('T', ' ', $data['tanggal_update']);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
                $normalized .= ':00';
            }
            $stok->tanggal_update = $normalized;
            $stok->save();
        }

        // Recalculate saldo setelah update
        $this->recalculateSaldo($stok->komoditas);

        return redirect()->route('admin.stok.index')
            ->with('success', 'Transaksi stok berhasil diperbarui.');
    }

    public function show(int $id)
    {
        $stok = Stok::with('user')->findOrFail($id);

        return view('admin.stok.show', [
            'stok' => $stok,
        ]);
    }
}