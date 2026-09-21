<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TujuanDistribusi;
use Illuminate\Http\Request;

class TujuanDistribusiController extends Controller
{
    /**
     * GET /api/tujuan-distribusi
     * Ambil tujuan distribusi dengan opsi search, paginasi, stats
     */
    public function index(Request $request)
    {
        $query = TujuanDistribusi::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Subquery untuk menghitung total_terkirim
        // Gunakan exact match: keterangan sama persis dengan nama, ATAU nama diikuti spasi (cegah "MBG 1" match "MBG 10")
        $query->addSelect([
            'total_terkirim' => \App\Models\Stok::selectRaw('COALESCE(SUM(jumlah), 0)')
                ->where('jenis_transaksi', 'keluar')
                ->where('status', 'aktif')
                ->where(function($q) {
                    $q->whereColumn('keterangan', 'tujuan_distribusi.nama')
                      ->orWhereColumn('keterangan', 'like', \Illuminate\Support\Facades\DB::raw('CONCAT(tujuan_distribusi.nama, \' %\')'))
                      ->orWhereRaw('keterangan REGEXP CONCAT("^", tujuan_distribusi.nama, "( |$)")');
                })
        ]);

        $query->orderByRaw('LENGTH(nama) ASC, nama ASC');

        // Jika diminta paginasi
        if ($request->has('page') || $request->has('with_stats')) {
            $tujuanList = $query->paginate($request->get('limit', 15));

            $response = $tujuanList->toArray();

            if ($request->has('with_stats')) {
                $totalTujuan = TujuanDistribusi::count();

                // Hanya hitung distribusi Beras (bukan Gabah)
                $stokBulanIni = \App\Models\Stok::where('jenis_transaksi', 'keluar')
                    ->where('komoditas', 'Beras')
                    ->where('status', 'aktif')
                    ->where(function($q) {
                        $q->whereMonth('tanggal_update', now()->month)
                          ->whereYear('tanggal_update', now()->year);
                    })
                    ->get();

                $totalDistribusiBulanIni = $stokBulanIni->sum('jumlah');

                // Cari tujuan terbanyak dengan exact match (cegah "MBG 1" match "MBG 10")
                $tujuanCount = [];
                $semuaTujuan = TujuanDistribusi::pluck('nama')->toArray();

                foreach ($stokBulanIni as $stok) {
                    foreach ($semuaTujuan as $nama) {
                        $ket = $stok->keterangan ?? '';
                        // Exact match: sama persis, atau nama diikuti spasi
                        if ($ket === $nama || str_starts_with($ket, $nama . ' ')) {
                            $tujuanCount[$nama] = ($tujuanCount[$nama] ?? 0) + $stok->jumlah;
                            break;
                        }
                    }
                }

                $tujuanTerbanyak = null;
                if (!empty($tujuanCount)) {
                    arsort($tujuanCount);
                    $tujuanTerbanyak = key($tujuanCount);
                }

                $response['summary'] = [
                    'total_tujuan' => $totalTujuan,
                    'total_dikirim_bulan_ini' => $totalDistribusiBulanIni,
                    'tujuan_terbanyak' => $tujuanTerbanyak ?? '-'
                ];
            }

            return response()->json($response);
        }

        // Default: return list flat tanpa paginasi untuk kebutuhan form dropdown lama
        $tujuanList = $query->get();
        return response()->json($tujuanList);
    }

    /**
     * GET /api/tujuan-distribusi/{id}/histori
     * Ambil histori transaksi distribusi untuk tujuan tertentu
     */
    public function histori(Request $request, TujuanDistribusi $tujuanDistribusi)
    {
        $namaTujuan = $tujuanDistribusi->nama;

        // Match: keterangan == nama, atau nama diikuti spasi agar "MBG 1" tidak match "MBG 10"
        $histori = \App\Models\Stok::where('jenis_transaksi', 'keluar')
            ->where('status', 'aktif')
            ->where(function($q) use ($namaTujuan) {
                $q->where('keterangan', $namaTujuan)
                  ->orWhere('keterangan', 'like', $namaTujuan . ' %');
            })
            ->orderBy('tanggal_update', 'desc')
            ->paginate($request->get('limit', 15));

        return response()->json($histori);
    }

    /**
     * GET /api/tujuan-distribusi/{id}
     */
    public function show(TujuanDistribusi $tujuanDistribusi)
    {
        return response()->json($tujuanDistribusi);
    }

    /**
     * POST /api/tujuan-distribusi
     * Buat tujuan distribusi baru (admin only)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:tujuan_distribusi,nama',
        ]);

        $tujuan = TujuanDistribusi::create($data);

        return response()->json($tujuan, 201);
    }

    /**
     * PUT /api/tujuan-distribusi/{id}
     * Update tujuan distribusi (admin only)
     */
    public function update(Request $request, TujuanDistribusi $tujuanDistribusi)
    {
        $data = $request->validate([
            'nama' => 'sometimes|required|string|max:255|unique:tujuan_distribusi,nama,' . $tujuanDistribusi->id,
        ]);

        $tujuanDistribusi->update($data);

        return response()->json($tujuanDistribusi);
    }

    /**
     * DELETE /api/tujuan-distribusi/{id}
     * Hapus tujuan distribusi (admin only)
     */
    public function destroy(TujuanDistribusi $tujuanDistribusi)
    {
        $tujuanDistribusi->delete();

        return response()->json(null, 204);
    }
}
