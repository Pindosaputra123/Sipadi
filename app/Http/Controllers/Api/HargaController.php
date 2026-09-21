<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Harga;
use App\Models\KonfigurasiHarga;
use Illuminate\Http\Request;

class HargaController extends Controller
{
    /**
     * GET /api/harga/aktif
     * Mengembalikan konfigurasi harga aktif dari tabel konfigurasi_harga
     * (digunakan oleh chatbot/n8n tool get_price_data)
     */
    public function aktif()
    {
        $config = KonfigurasiHarga::where('is_active', true)
            ->latest('berlaku_mulai')
            ->first();

        if (!$config) {
            return response()->json(['message' => 'Belum ada harga aktif'], 404);
        }

        return response()->json([
            'harga_beli_gabah'  => $config->harga_beli_gabah,
            'harga_jual_beras'  => $config->harga_jual_beras,
            'ongkos_giling'     => $config->ongkos_giling ?? null,
            'rasio_konversi'    => $config->rasio_konversi ?? null,
            'berlaku_mulai'     => optional($config->berlaku_mulai)->format('Y-m-d'),
            'is_active'         => $config->is_active,
        ]);
    }

    public function index()
    {
        return Harga::orderByDesc('tanggal_berlaku')->paginate(15);
    }

    public function show(Harga $harga)
    {
        return $harga;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'komoditas' => 'required|string|max:255',
            'harga_per_kg' => 'required|numeric|min:0',
            'tanggal_berlaku' => 'required|date',
            'sumber' => 'nullable|string',
        ]);

        $harga = Harga::create($data);

        return response()->json($harga, 201);
    }

    public function update(Request $request, Harga $harga)
    {
        $data = $request->validate([
            'komoditas' => 'sometimes|required|string|max:255',
            'harga_per_kg' => 'sometimes|required|numeric|min:0',
            'tanggal_berlaku' => 'sometimes|required|date',
            'sumber' => 'nullable|string',
        ]);

        $harga->update($data);

        return response()->json($harga);
    }

    public function destroy(Harga $harga)
    {
        $harga->delete();

        return response()->json(null, 204);
    }

    public function calculate(Request $request)
    {
        $data = $request->validate([
            'harga_jual' => 'required|numeric|min:0',
            'biaya_bahan' => 'required|numeric|min:0',
            'biaya_olah' => 'required|numeric|min:0',
            'overhead' => 'required|numeric|min:0',
        ]);

        $hpp = round($data['biaya_bahan'] + $data['biaya_olah'] + $data['overhead'], 2);
        $margin = $hpp > 0
            ? round((($data['harga_jual'] - $hpp) / $hpp) * 100, 2)
            : null;

        return response()->json([
            'harga_jual' => $data['harga_jual'],
            'hpp' => $hpp,
            'margin' => $margin,
            'margin_percent' => $margin,
        ]);
    }
}
