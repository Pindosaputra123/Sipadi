<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Harga;
use App\Models\KonfigurasiHarga;
use Illuminate\Http\Request;

class HargaController extends Controller
{
    public function index()
    {
        $konfigurasi = KonfigurasiHarga::orderBy('berlaku_mulai', 'desc')->get();
        $activeConfig = $konfigurasi->firstWhere('is_active', true) ?? $konfigurasi->first();
        return view('admin.harga.index', compact('konfigurasi', 'activeConfig'));
    }

    public function create()
    {
        $activePrice = KonfigurasiHarga::where('is_active', true)->first() ?? KonfigurasiHarga::latest('berlaku_mulai')->first();
        return view('admin.harga.form', compact('activePrice'));
    }

    public function store(Request $request)
    {
        $this->normalizeNumericInputs($request);
        $validated = $request->validate([
            'harga_beli_gabah' => 'required|numeric|min:0',
            'harga_jual_beras' => 'required|numeric|min:0',
            'berlaku_mulai' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->has('is_active');
        $validated['is_active'] = $isActive;

        if ($isActive) {
            KonfigurasiHarga::query()->update(['is_active' => false]);
        }

        $config = KonfigurasiHarga::create($validated);

        if ($isActive) {
            $this->syncToHargaTable($config);
        }

        return redirect()
            ->route('admin.harga.index')
            ->with('success', 'Konfigurasi Harga berhasil ditambahkan');
    }

    public function edit(KonfigurasiHarga $harga)
    {
        $activePrice = KonfigurasiHarga::where('is_active', true)->first() ?? KonfigurasiHarga::latest('berlaku_mulai')->first();
        return view('admin.harga.form', compact('harga', 'activePrice'));
    }

    public function update(Request $request, KonfigurasiHarga $harga)
    {
        $this->normalizeNumericInputs($request);
        $validated = $request->validate([
            'harga_beli_gabah' => 'required|numeric|min:0',
            'harga_jual_beras' => 'required|numeric|min:0',
            'berlaku_mulai' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->has('is_active');
        $validated['is_active'] = $isActive;

        if ($isActive) {
            KonfigurasiHarga::query()->update(['is_active' => false]);
        }

        $harga->update($validated);

        if ($isActive) {
            $this->syncToHargaTable($harga->fresh());
        }

        return redirect()
            ->route('admin.harga.index')
            ->with('success', 'Konfigurasi Harga berhasil diperbarui');
    }

    public function destroy(KonfigurasiHarga $harga)
    {
        if ($harga->is_active) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus konfigurasi yang sedang aktif!');
        }

        $harga->delete();
        return redirect()->back()->with('success', 'Konfigurasi Harga berhasil dihapus');
    }



    public function activate(KonfigurasiHarga $harga)
    {
        KonfigurasiHarga::query()->update(['is_active' => false]);
        $harga->update(['is_active' => true]);

        $this->syncToHargaTable($harga->fresh());

        return redirect()->back()->with('success', 'Konfigurasi Harga berhasil diaktifkan');
    }

    /**
     * Sinkronisasi harga aktif dari konfigurasi_harga ke tabel harga
     * agar endpoint /api/harga dan chatbot selalu menampilkan data terkini.
     */
    private function syncToHargaTable(KonfigurasiHarga $config): void
    {
        $tanggal = optional($config->berlaku_mulai)->format('Y-m-d') ?? now()->format('Y-m-d');

        Harga::updateOrCreate(
            ['komoditas' => 'Gabah'],
            [
                'harga_per_kg'    => $config->harga_beli_gabah,
                'tanggal_berlaku' => $tanggal,
                'sumber'          => 'Manajemen Harga SIMHP',
            ]
        );

        Harga::updateOrCreate(
            ['komoditas' => 'Beras'],
            [
                'harga_per_kg'    => $config->harga_jual_beras,
                'tanggal_berlaku' => $tanggal,
                'sumber'          => 'Manajemen Harga SIMHP',
            ]
        );
    }

    /**
     * Normalize currency / formatted numeric inputs to plain numeric strings
     * so validation accepts locale-formatted values like "760.000" or "13.500".
     */
    private function normalizeNumericInputs(Request $request)
    {
        $fields = ['harga_beli_gabah', 'harga_jual_beras'];
        foreach ($fields as $f) {
            if (!$request->has($f)) continue;
            $val = (string) $request->input($f);
            if ($val === '' || is_null($val)) continue;

            // The JS on submit strips all non-digit chars before submitting,
            // so value arriving here should already be a plain integer like "13500".
            // As a safety net, handle both formats:
            // Indonesian thousands: "13.500" → remove dots → "13500"
            // European decimal: "13,5" → convert comma to dot → "13.5"
            // Mixed: "1.234,56" → remove dots, comma→dot → "1234.56"
            if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
                // Both present: dot = thousands, comma = decimal
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } elseif (strpos($val, ',') !== false) {
                // Only comma: treat as decimal separator
                $val = str_replace(',', '.', $val);
            } else {
                // Only dots or none: strip all non-digit chars (dots = thousands sep)
                $val = preg_replace('/[^0-9]/', '', $val);
            }

            if (is_numeric($val)) {
                $request->merge([$f => $val]);
            }
        }
    }
}