<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Panen;
use App\Models\Distribusi;
use App\Models\Petani;
use App\Models\KonfigurasiHarga;
use App\Models\Stok;
use Illuminate\Support\Facades\Schema;

class LaporanController extends Controller
{
    /**
     * Helper: ambil HPP per kg dari konfigurasi aktif.
     */
    private function getHppPerKg(): float
    {
        $config = KonfigurasiHarga::where('is_active', true)->first();
        if ($config && $config->rasio_konversi > 0) {
            return round(($config->harga_beli_gabah / $config->rasio_konversi) + $config->ongkos_giling, 2);
        }
        return 0;
    }

    /**
     * GET /api/laporan/panen
     * Query params: petani_id, dari, sampai
     */
    public function panen(Request $request)
    {
        $dari    = $request->get('dari', date('Y-m-01'));
        $sampai  = $request->get('sampai', date('Y-m-d'));
        $petaniId = $request->get('petani_id');

        $hppPerKg = $this->getHppPerKg();

        // Query panen
        $query = Panen::with(['petani', 'lahan'])
            ->whereBetween('tanggal_panen', [$dari, $sampai]);
        if ($petaniId) {
            $query->whereHas('petani', fn($q) => $q->where('petani.id', $petaniId));
        }
        $rows = $query->orderBy('tanggal_panen', 'desc')->get();

        $totalPanen       = $rows->sum('jumlah_gabah');
        $totalDistribusi  = Distribusi::whereBetween('tanggal_distribusi', [$dari, $sampai])->sum('jumlah_distribusi');
        $estimasiMargin   = $totalDistribusi * $hppPerKg;

        // Grafik per bulan
        $grafik = $rows->groupBy(fn($item) => optional($item->tanggal_panen)->format('Y-m'))
            ->map(fn($items, $bulan) => [
                'bulan'       => optional($items->first()->tanggal_panen)->format('M Y'),
                'total_gabah' => $items->sum('jumlah_gabah'),
            ])
            ->values();

        // Detail rows
        $detail = $rows->map(function ($row) use ($hppPerKg) {
            // konversi_beras menyimpan hasil beras dalam kg (bukan persentase)
            $beras = round((float) ($row->konversi_beras ?? ($row->jumlah_gabah * 0.615)));
            return [
                'id'               => $row->id,
                'petani'           => $row->petani->nama ?? '-',
                'lahan'            => $row->lahan->luas_lahan ?? '-',
                'jumlah_gabah'     => (float) $row->jumlah_gabah,
                'beras_dihasilkan' => (float) $beras,
                'tanggal_panen'    => optional($row->tanggal_panen)->format('Y-m-d'),
                'hpp_estimasi'     => $hppPerKg,
            ];
        })->values();

        return response()->json([
            'summary' => [
                'total_panen'      => (float) $totalPanen,
                'total_distribusi' => (float) $totalDistribusi,
                'estimasi_margin'  => (float) $estimasiMargin,
            ],
            'grafik' => $grafik,
            'detail' => $detail,
        ]);
    }

    /**
     * GET /api/laporan/stok
     * Query params: komoditas, dari, sampai
     */
    public function stok(Request $request)
    {
        $dari      = $request->get('dari', date('Y-m-01'));
        $sampai    = $request->get('sampai', date('Y-m-d'));
        $komoditas = $request->get('komoditas');

        // Cari nama kolom tanggal yang tersedia
        $dateColumn = 'created_at';
        foreach (['tanggal_update', 'tanggal', 'created_at'] as $col) {
            if (Schema::hasColumn('stok_beras', $col)) {
                $dateColumn = $col;
                break;
            }
        }

        $query = Stok::with('gudang')->whereBetween($dateColumn, [$dari . ' 00:00:00', $sampai . ' 23:59:59']);
        if ($komoditas) {
            $query->where('komoditas', $komoditas);
        }
        $rows = $query->get();

        $totalStok    = $rows->sum('jumlah_stok');
        $gudangTerdata = $rows->pluck('gudang.nama_gudang')->unique()->filter()->count();
        $stokKurang   = $rows->filter(fn($item) => $item->jumlah_stok < $item->batas_minimum)->count();

        // Grafik per tanggal
        $grafik = $rows->groupBy(fn($item) => optional($item->{$dateColumn})->format('Y-m-d'))
            ->map(fn($items, $tgl) => [
                'tanggal'     => $tgl,
                'jumlah_stok' => $items->sum('jumlah_stok'),
            ])
            ->values();

        // Detail rows
        $detail = $rows->map(function ($row) use ($dateColumn) {
            $stok    = $row->jumlah_stok;
            $minimum = $row->batas_minimum;
            return [
                'id'             => $row->id,
                'gudang'         => $row->gudang->nama_gudang ?? '-',
                'komoditas'      => $row->komoditas ?? '-',
                'jumlah_stok'    => (float) $stok,
                'batas_minimum'  => (float) $minimum,
                'tanggal_update' => optional($row->{$dateColumn})->format('Y-m-d H:i:s'),
                'status'         => $stok < $minimum ? 'kurang' : 'cukup',
            ];
        })->values();

        return response()->json([
            'summary' => [
                'total_stok'    => (float) $totalStok,
                'gudang_terdata' => (int) $gudangTerdata,
                'stok_kurang'   => (int) $stokKurang,
            ],
            'grafik' => $grafik,
            'detail' => $detail,
        ]);
    }

    /**
     * GET /api/laporan/margin
     * Query params: petani_id, dari, sampai
     */
    public function margin(Request $request)
    {
        $dari     = $request->get('dari', date('Y-m-01'));
        $sampai   = $request->get('sampai', date('Y-m-d'));
        $petaniId = $request->get('petani_id');

        $hppPerKg = $this->getHppPerKg();

        $query = Panen::with(['petani', 'lahan'])
            ->whereBetween('tanggal_panen', [$dari, $sampai]);
        if ($petaniId) {
            $query->whereHas('petani', fn($q) => $q->where('petani.id', $petaniId));
        }
        $rows = $query->orderBy('tanggal_panen', 'desc')->get();

        $totalPanen       = $rows->sum('jumlah_gabah');
        $totalDistribusi  = Distribusi::whereBetween('tanggal_distribusi', [$dari, $sampai])->sum('jumlah_distribusi');
        $estimasiMargin   = $totalDistribusi * $hppPerKg;

        // Grafik per bulan
        $grafik = $rows->groupBy(fn($item) => optional($item->tanggal_panen)->format('Y-m'))
            ->map(fn($items, $bulan) => [
                'bulan'       => optional($items->first()->tanggal_panen)->format('M Y'),
                'total_gabah' => $items->sum('jumlah_gabah'),
            ])
            ->values();

        // Detail margin per panen
        $detail = $rows->map(function ($row) use ($hppPerKg) {
            // konversi_beras menyimpan hasil beras dalam kg (bukan persentase)
            $beras = round((float) ($row->konversi_beras ?? ($row->jumlah_gabah * 0.615)));
            return [
                'id'               => $row->id,
                'petani'           => $row->petani->nama ?? '-',
                'lahan'            => $row->lahan->luas_lahan ?? '-',
                'jumlah_gabah'     => (float) $row->jumlah_gabah,
                'beras_dihasilkan' => (float) $beras,
                'tanggal_panen'    => optional($row->tanggal_panen)->format('Y-m-d'),
                'hpp_estimasi'     => $hppPerKg,
                'status'           => $row->status ?? 'selesai',
            ];
        })->values();

        return response()->json([
            'summary' => [
                'total_distribusi' => (float) $totalDistribusi,
                'total_panen'      => (float) $totalPanen,
                'estimasi_margin'  => (float) $estimasiMargin,
            ],
            'grafik' => $grafik,
            'detail' => $detail,
        ]);
    }
}
