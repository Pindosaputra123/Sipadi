<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertConfiguration;
use App\Models\KonfigurasiHarga;
use App\Models\Stok;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $dateColumn = $this->getDateColumn();
        $stokBeras = $this->getCurrentStock('Beras');
        $stokGabah = $this->getCurrentStock('Gabah');

        $totalBerasKeluar = Stok::where('komoditas', 'Beras')
            ->where('jenis_transaksi', 'keluar')
            ->whereYear($dateColumn, Carbon::now()->year)
            ->whereMonth($dateColumn, Carbon::now()->month)
            ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); })
            ->sum('jumlah');

        $config = KonfigurasiHarga::where('is_active', true)
            ->orderByDesc('berlaku_mulai')
            ->first();

        $hargaBeliGabah = optional($config)->harga_beli_gabah ?? 760000;
        $ongkosGiling = optional($config)->ongkos_giling ?? 700;
        $hargaJualBeras = optional($config)->harga_jual_beras ?? 13500;
        $rasioKonversi = optional($config)->rasio_konversi ?: 10;
        $hppPerKg = $rasioKonversi > 0 ? round(($hargaBeliGabah / $rasioKonversi) + $ongkosGiling) : 0;
        $marginPerKg = max(0, $hargaJualBeras - $hppPerKg);
        $marginPercent = $hargaJualBeras > 0 ? round(($marginPerKg / $hargaJualBeras) * 100, 1) : 0;

        $alertAktif = Alert::where('status', 'aktif')->get();
        $alertProses = Alert::whereIn('status', ['proses', 'dalam_penanganan'])->get();
        $alertOpenCount = $alertAktif->count() + $alertProses->count();

        $distribusiTerkini = Stok::where('jenis_transaksi', 'keluar')
            ->where('komoditas', 'Beras')
            ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); })
            ->orderByDesc($dateColumn)
            ->limit(200)
            ->get()
            ->map(function ($item) {
                $raw = $item->tanggal ?? $item->tanggal_update ?? $item->created_at;
                $tanggalFormatted = $raw ? \Carbon\Carbon::parse($raw)->translatedFormat('d M Y, H:i') : '-';
                return (object) [
                    'tujuan'      => $item->keterangan ?: 'Distribusi',
                    'jenis_tujuan' => 'Tujuan',
                    'komoditas'   => $item->komoditas,
                    'jumlah'      => $item->jumlah,
                    'tanggal'     => $tanggalFormatted,
                ];
            });

        $chartMonths = collect(range(5, 0))->map(fn($monthsAgo) => Carbon::now()->subMonths($monthsAgo));
        $chartLabels = $chartMonths->map(fn($date) => $date->format('M'))->toArray();

        $stokBerasHistory = $chartMonths->map(function ($month) use ($dateColumn) {
            return (float) Stok::where('komoditas', 'Beras')
                ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); })
                ->whereYear($dateColumn, $month->year)
                ->whereMonth($dateColumn, $month->month)
                ->orderByDesc($dateColumn)
                ->value('jumlah_stok') ?: 0;
        })->toArray();

        $trenPanenGabah = $chartMonths->map(function ($month) use ($dateColumn) {
            return (float) Stok::where('komoditas', 'Gabah')
                ->where('jenis_transaksi', 'masuk')
                ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); })
                ->whereYear($dateColumn, $month->year)
                ->whereMonth($dateColumn, $month->month)
                ->sum('jumlah');
        })->toArray();

        $alertConfig    = AlertConfiguration::first();
        $kapasitasBeras = $alertConfig->kapasitas_max_beras ?? 1000;
        $kapasitasGabah = $alertConfig->kapasitas_max_gabah ?? 2000;
        $targetBulan    = $alertConfig->target_pasar ?? 9000;
        $targetChart    = array_fill(0, count($chartLabels), $targetBulan);

        return view('admin.dashboard', compact(
            'stokBeras',
            'stokGabah',
            'alertAktif',
            'alertOpenCount',
            'distribusiTerkini',
            'chartLabels',
            'stokBerasHistory',
            'trenPanenGabah',
            'targetChart',
            'targetBulan',
            'kapasitasBeras',
            'kapasitasGabah',
            'hargaBeliGabah',
            'ongkosGiling',
            'hargaJualBeras',
            'hppPerKg',
            'marginPerKg',
            'marginPercent',
            'totalBerasKeluar'
        ));
    }

    private function getDateColumn(): string
    {
        if (Schema::hasColumn('stok_beras', 'tanggal_update')) {
            return 'tanggal_update';
        }

        if (Schema::hasColumn('stok_beras', 'tanggal')) {
            return 'tanggal';
        }

        return 'created_at';
    }

    private function getCurrentStock(string $komoditas): float
    {
        $query = Stok::where('komoditas', $komoditas)
            ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); });

        $dateColumn = $this->getDateColumn();

        return (float) ($query->orderByDesc($dateColumn)->value('jumlah_stok') ?: 0);
    }
}