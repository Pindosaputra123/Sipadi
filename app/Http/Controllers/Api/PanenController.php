<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\KonfigurasiHarga;
use App\Models\Panen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Petani;
use App\Models\Lahan;

class PanenController extends Controller
{
    // ── Helper: tambahkan foto_bukti_url ke item panen ────────────────
    private function appendFotoUrl($panen): array
    {
        $arr = is_array($panen) ? $panen : $panen->toArray();
        $arr['foto_bukti_url'] = !empty($arr['foto_bukti'])
            ? asset('storage/' . $arr['foto_bukti'])
            : null;
        return $arr;
    }

    public function index()
    {
        $paginated = Panen::with('lahan.petani')->paginate(15);
        $paginated->getCollection()->transform(function ($item) {
            $item->foto_bukti_url = $item->foto_bukti ? asset('storage/' . $item->foto_bukti) : null;
            return $item;
        });
        return $paginated;
    }

    public function show(Panen $panen)
    {
        $panen->load('lahan.petani');
        $panen->foto_bukti_url = $panen->foto_bukti ? asset('storage/' . $panen->foto_bukti) : null;
        return $panen;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lahan_id'           => 'nullable|integer|exists:lahan,id',
            'tanggal_panen'      => 'required|date',
            'jumlah_gabah'       => 'required|numeric|min:0',
            'harga_gabah_per_kg' => 'nullable|numeric|min:0',
            // konversi_factor dikirim Flutter sebagai 0–1 (misal 0.615 = 61.5%)
            'konversi_factor'    => 'nullable|numeric|min:0|max:1',
            'catatan'            => 'nullable|string',
            // field dari Flutter
            'musim_tanam'        => 'nullable|string|max:100',
            'komoditas'          => 'nullable|string|max:50',
            'petani_id'          => 'required_without:lahan_id|integer|exists:petani,id',
            // Foto bukti panen — WAJIB via multipart form-data
            'foto_bukti'         => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (empty($data['lahan_id']) && !empty($data['petani_id'])) {
            $petani = Petani::find($data['petani_id']);
            $lahan = $petani?->lahan()->first();
            
            if (! $lahan && $petani) {
                if (! empty($petani->luas_lahan) && $petani->luas_lahan > 0) {
                    $lahan = Lahan::create([
                        'petani_id' => $petani->id,
                        'nama_lahan' => 'Lahan utama',
                        'luas' => $petani->luas_lahan,
                        'lokasi' => $petani->alamat,
                        'status' => 'aktif',
                    ]);
                }
            }
            
            if (! $lahan) {
                return response()->json(['message' => 'Petani belum memiliki lahan terdaftar.'], 422);
            }
            $data['lahan_id'] = $lahan->id;
        }

        // ── Snapshot harga gabah per kg dari konfigurasi aktif ──────────
        // Jika Flutter tidak kirim harga, ambil harga aktif dari DB (historis tidak berubah walau harga master diubah)
        if (empty($data['harga_gabah_per_kg'])) {
            $activePrice = KonfigurasiHarga::where('is_active', true)->first()
                ?? KonfigurasiHarga::latest('berlaku_mulai')->first();
            $data['harga_gabah_per_kg'] = $activePrice?->harga_beli_gabah ?? 0;
        }

        // Hitung beras hasil: gabah × factor, simpan sebagai kg
        $factor = $data['konversi_factor'] ?? 0.615;
        $data['konversi_beras'] = round($data['jumlah_gabah'] * $factor, 2);

        // Map musim_tanam (Flutter) → musim (DB)
        if (!empty($data['musim_tanam'])) {
            $data['musim'] = $data['musim_tanam'];
        }

        // ── Upload foto bukti panen ──────────────────────────────────────
        $fotoBuktiPath = null;
        if ($request->hasFile('foto_bukti')) {
            $file = $request->file('foto_bukti');
            $filename = now()->format('Y-m-d-His') . '-' . Str::random(12) . '.' . $file->getClientOriginalExtension();
            $fotoBuktiPath = $file->storeAs('panen/bukti', $filename, 'public');
            $data['foto_bukti'] = $fotoBuktiPath;
        }

        // Bersihkan field yang tidak ada di tabel panen
        $petaniId = $data['petani_id'] ?? null;
        unset($data['konversi_factor'], $data['musim_tanam'], $data['komoditas'], $data['petani_id']);

        $panen = Panen::create($data);
        $panen->load('lahan.petani');

        // ── Otomatis tambah Gabah Masuk di Stok Gudang ──────────────────
        // Konsisten dengan web Admin\PanenController@store
        try {
            $dateColumn = Schema::hasColumn('stok_beras', 'tanggal_update')
                ? 'tanggal_update'
                : (Schema::hasColumn('stok_beras', 'tanggal') ? 'tanggal' : 'created_at');

            $stokSebelumnya = (float) (DB::table('stok_beras')
                ->where('komoditas', 'Gabah')
                ->where('status', 'aktif')
                ->orderByDesc($dateColumn)
                ->orderByDesc('id')
                ->value('jumlah_stok') ?: 0);

            $batasMin = (float) (DB::table('stok_beras')
                ->where('komoditas', 'Gabah')
                ->where('status', 'aktif')
                ->orderByDesc($dateColumn)
                ->orderByDesc('id')
                ->value('batas_minimum') ?: 500);

            $stokBaru   = $stokSebelumnya + $data['jumlah_gabah'];
            $tanggalNow = now()->format('Y-m-d H:i:s');
            $petaniNama = $panen->lahan?->petani?->nama ?? 'Petani';
            $musimLabel = $data['musim'] ?? '-';

            $stokPayload = [
                'gudang_id'       => Gudang::first()?->id ?? 1,
                'jenis_transaksi' => 'masuk',
                'komoditas'       => 'Gabah',
                'jumlah'          => $data['jumlah_gabah'],
                'jumlah_stok'     => $stokBaru,
                'batas_minimum'   => $batasMin,
                'keterangan'      => 'Panen: ' . $petaniNama . ' – Musim ' . $musimLabel,
                'catatan'         => null,
                'status'          => 'aktif',
                'foto_bukti'      => $fotoBuktiPath,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            if (Schema::hasColumn('stok_beras', 'user_id')) {
                $stokPayload['user_id'] = Auth::id();
            }
            if (Schema::hasColumn('stok_beras', 'tanggal_update')) {
                $stokPayload['tanggal_update'] = $tanggalNow;
            } elseif (Schema::hasColumn('stok_beras', 'tanggal')) {
                $stokPayload['tanggal'] = $tanggalNow;
            }

            DB::table('stok_beras')->insert($stokPayload);
        } catch (\Throwable $e) {
            // Jangan gagalkan request panen jika stok insert error — log saja
            Log::error('Auto Gabah Masuk gagal setelah simpan panen: ' . $e->getMessage());
        }
        // ────────────────────────────────────────────────────────────────

        $responseData = $panen->toArray();
        $responseData['foto_bukti_url'] = $fotoBuktiPath ? asset('storage/' . $fotoBuktiPath) : null;

        return response()->json($responseData, 201);
    }

    public function update(Request $request, Panen $panen)
    {
        $data = $request->validate([
            'lahan_id'           => 'sometimes|required|integer|exists:lahan,id',
            'tanggal_panen'      => 'sometimes|required|date',
            'jumlah_gabah'       => 'sometimes|required|numeric|min:0',
            'harga_gabah_per_kg' => 'nullable|numeric|min:0',
            'konversi_factor'    => 'nullable|numeric|min:0|max:1',
            'catatan'            => 'nullable|string',
            'musim_tanam'        => 'nullable|string|max:100',
            'komoditas'          => 'nullable|string|max:50',
            'petani_id'          => 'nullable|integer',
        ]);

        if (isset($data['konversi_factor']) || isset($data['jumlah_gabah'])) {
            $factor      = $data['konversi_factor'] ?? 0.615;
            $jumlahGabah = $data['jumlah_gabah'] ?? $panen->jumlah_gabah;
            $data['konversi_beras'] = round($jumlahGabah * $factor, 2);
        }

        if (!empty($data['musim_tanam'])) {
            $data['musim'] = $data['musim_tanam'];
        }

        unset($data['konversi_factor'], $data['musim_tanam'], $data['komoditas'], $data['petani_id']);

        $panen->update($data);
        $panen->refresh()->load('lahan.petani');
        $panen->foto_bukti_url = $panen->foto_bukti ? asset('storage/' . $panen->foto_bukti) : null;

        return response()->json($panen);
    }

    public function destroy(Panen $panen)
    {
        $panen->delete();

        return response()->json(null, 204);
    }
}
