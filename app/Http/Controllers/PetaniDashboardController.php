<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KonfigurasiHarga;
use App\Models\Panen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PetaniDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $petani = $user->petani;

        if (!$petani) {
            abort(404, 'Data petani tidak ditemukan.');
        }

        $totalLahan = $petani->lahan()->count();
        $totalPanen = Panen::whereHas('lahan', function ($query) use ($petani) {
            $query->where('petani_id', $petani->id);
        })->count();

        $panensQuery = Panen::whereHas('lahan', function ($query) use ($petani) {
            $query->where('petani_id', $petani->id);
        });

        // Hitung total gabah keseluruhan sebelum pagination
        $totalGabah = $panensQuery->sum('jumlah_gabah');

        // Ambil data panen list biasa (paginate 5) untuk tabel
        $panens = Panen::whereHas('lahan', function ($query) use ($petani) {
                $query->where('petani_id', $petani->id);
            })
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->paginate(5);

        // Rekap per tanggal untuk panel di sebelah kanan
        $rekapTanggal = Panen::selectRaw('tanggal_panen, SUM(jumlah_gabah) as total_gabah, COUNT(*) as jumlah_entri, SUM(jumlah_gabah * harga_gabah_per_kg) as total_penghasilan')
            ->whereHas('lahan', function ($query) use ($petani) {
                $query->where('petani_id', $petani->id);
            })
            ->groupBy('tanggal_panen')
            ->orderByDesc('tanggal_panen')
            ->take(30)
            ->get();

        $activePrice = KonfigurasiHarga::where('is_active', true)->first();

        return view('petani.dashboard', compact('petani', 'totalLahan', 'totalPanen', 'panens', 'activePrice', 'totalGabah', 'rekapTanggal'));
    }
}
