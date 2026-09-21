<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TujuanDistribusi;
use App\Models\Stok;
use Illuminate\Http\Request;

class TujuanDistribusiController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $query = TujuanDistribusi::query();
        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        // Hitung total_terkirim tiap tujuan (exact match atau nama + spasi)
        $allTujuans = $query->get()->map(function ($t) {
            $total = Stok::where('jenis_transaksi', 'keluar')
                ->where('status', 'aktif')
                ->where(function($q) use ($t) {
                    $q->where('keterangan', $t->nama)
                      ->orWhere('keterangan', 'like', $t->nama . ' %');
                })
                ->sum('jumlah');
            $t->total_terkirim = (float) $total;
            return $t;
        })->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // Stat cards
        $totalTujuan = TujuanDistribusi::count();
        $bulanIni = now()->month;
        $tahunIni = now()->year;
        $stokBulanIni = Stok::where('jenis_transaksi', 'keluar')
            ->where('komoditas', 'Beras')
            ->where('status', 'aktif')
            ->whereMonth('tanggal_update', $bulanIni)
            ->whereYear('tanggal_update', $tahunIni)
            ->get();
        $totalDikirimBulanIni = $stokBulanIni->sum('jumlah');

        // Tujuan terbanyak bulan ini
        $semuaNama = TujuanDistribusi::pluck('nama')->toArray();
        $tujuanCount = [];
        foreach ($stokBulanIni as $stok) {
            $ket = $stok->keterangan ?? '';
            foreach ($semuaNama as $nama) {
                if ($ket === $nama || str_starts_with($ket, $nama . ' ')) {
                    $tujuanCount[$nama] = ($tujuanCount[$nama] ?? 0) + $stok->jumlah;
                    break;
                }
            }
        }
        arsort($tujuanCount);
        $tujuanTerbanyak = key($tujuanCount) ?? '-';

        // Pagination manual — 10 per page
        $perPage = 10;
        $currentPage = (int) $request->get('page', 1);
        $total = $allTujuans->count();
        $tujuans = $allTujuans->forPage($currentPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('admin.tujuan-distribusi.index', compact(
            'tujuans', 'search', 'currentPage', 'lastPage', 'total',
            'totalTujuan', 'totalDikirimBulanIni', 'tujuanTerbanyak'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:tujuan_distribusi,nama',
        ]);

        $tujuan = TujuanDistribusi::create(['nama' => trim($data['nama'])]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => $tujuan]);
        }

        return redirect()->back()->with('success', 'Tujuan distribusi ditambahkan.');
    }

    public function destroy(int $id)
    {
        $tujuan = TujuanDistribusi::findOrFail($id);

        // Prevent deletion if used in stok_beras.keterangan
        $used = Stok::whereNotNull('keterangan')->where('keterangan', 'like', "%{$tujuan->nama}%")->exists();
        if ($used) {
            return redirect()->back()->with('error', 'Tujuan distribusi tidak dapat dihapus karena sudah digunakan di transaksi.');
        }

        $tujuan->delete();

        return redirect()->back()->with('success', 'Tujuan distribusi dihapus.');
    }
}
