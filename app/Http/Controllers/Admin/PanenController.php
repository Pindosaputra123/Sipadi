<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Panen;
use App\Models\Petani;
use App\Models\Lahan;
use App\Models\KonfigurasiHarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PanenController extends Controller
{
    public function index()
    {
        $petanis = Petani::orderBy('nama')->get();
        $panenList = Panen::with('lahan.petani')
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->paginate(10);
        $activePrice = KonfigurasiHarga::where('is_active', true)->first() ?? KonfigurasiHarga::latest('berlaku_mulai')->first();

        return view('admin.panen.index', compact('petanis', 'panenList', 'activePrice'));
    }

    public function create()
    {
        $lahans = Lahan::with('petani')->get();
        return view('admin.panen.create', compact('lahans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'petani_id' => 'required|exists:petani,id',
            'musim' => 'required|string|max:100',
            'tanggal_panen' => 'required|date',
            'jumlah_gabah' => 'required|numeric|min:0.1',
            'komoditas' => 'nullable|string|max:50',
            'catatan' => 'nullable|string',
            'foto_bukti' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $petani = Petani::find($validated['petani_id']);
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
            return back()
                ->withErrors(['petani_id' => 'Petani belum memiliki lahan terdaftar.'])
                ->withInput();
        }

        $activePrice = \App\Models\KonfigurasiHarga::where('is_active', true)->first() ?? \App\Models\KonfigurasiHarga::latest('berlaku_mulai')->first();
        $hargaSaatIni = $activePrice ? $activePrice->harga_beli_gabah : 0;

        $fotoBuktiPath = $request->file('foto_bukti')->store('panen/bukti', 'public');

        $panen = Panen::create([
            'lahan_id' => $lahan->id,
            'tanggal_panen' => $validated['tanggal_panen'],
            'jumlah_gabah' => $validated['jumlah_gabah'],
            'harga_gabah_per_kg' => $hargaSaatIni,
            'konversi_beras' => 0,
            'musim' => $validated['musim'],
            'foto_bukti' => $fotoBuktiPath,
            'catatan' => trim(implode(' ', array_filter([
                $validated['komoditas'] ? 'Komoditas: ' . $validated['komoditas'] . '.' : null,
                $validated['catatan'] ?? null,
            ]))),
        ]);

        // ── Otomatis tambah Gabah Masuk di Stok Gudang ──────────────────
        $dateColumn = Schema::hasColumn('stok_beras', 'tanggal_update')
            ? 'tanggal_update'
            : (Schema::hasColumn('stok_beras', 'tanggal') ? 'tanggal' : 'created_at');

        $stokSebelumnya = (float) (DB::table('stok_beras')
            ->where('komoditas', 'Gabah')
            ->where('status', 'aktif')
            ->orderByDesc($dateColumn)
            ->orderByDesc('id')
            ->value('jumlah_stok') ?: 0);

        $stokBaru = $stokSebelumnya + $validated['jumlah_gabah'];
        $tanggalNow = now()->format('Y-m-d H:i:s');

        $stokPayload = [
            'gudang_id'       => 1,
            'jenis_transaksi' => 'masuk',
            'komoditas'       => 'Gabah',
            'jumlah'          => $validated['jumlah_gabah'],
            'jumlah_stok'     => $stokBaru,
            'keterangan'      => 'Panen: ' . ($panen->lahan->petani->nama ?? 'Petani') . ' – Musim ' . $validated['musim'],
            'catatan'         => $validated['catatan'] ?? null,
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
        // ────────────────────────────────────────────────────────────────

        return redirect()->route('admin.panen.index')
            ->with('success', 'Data panen berhasil ditambahkan dan Gabah Masuk otomatis tercatat di Stok Gudang.');
    }

    public function show($id)
    {
        $panen = Panen::with('lahan.petani')->findOrFail($id);
        return view('admin.panen.show', compact('panen'));
    }

    public function edit($id)
    {
        $panen = Panen::findOrFail($id);
        $lahans = Lahan::with('petani')->get();
        return view('admin.panen.edit', compact('panen', 'lahans'));
    }

    public function update(Request $request, $id)
    {
        $panen = Panen::findOrFail($id);

        $validated = $request->validate([
            'lahan_id' => 'required|exists:lahan,id',
            'jumlah_gabah' => 'required|numeric|min:0.1',
            'tanggal_panen' => 'required|date',
            'musim' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ], [
            'lahan_id.exists' => 'Lahan tidak valid.',
            'jumlah_gabah.required' => 'Jumlah gabah harus diisi.',
            'tanggal_panen.required' => 'Tanggal panen harus diisi.',
        ]);

        $panen->update($validated);

        return redirect()->route('admin.panen.index')
            ->with('success', 'Data panen berhasil diperbarui');
    }

    public function destroy($id)
    {
        $panen = Panen::findOrFail($id);
        $panen->delete();

        return redirect()->route('admin.panen.index')
            ->with('success', 'Data panen berhasil dihapus');
    }
}
