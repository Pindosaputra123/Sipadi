<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\Panen;
use App\Models\Distribusi;
use App\Models\Petani;
use App\Models\KonfigurasiHarga;
use App\Models\Stok;
use App\Models\AlertConfiguration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index()
    {
        $jenis = request('jenis', 'stok');
        $dari = request('dari', date('Y-m-01'));
        $sampai = request('sampai', date('Y-m-d'));
        $petaniId = request('petani_id');
        $komoditas = request('komoditas');

        $petanis = Petani::orderBy('nama')->get();
        $config = KonfigurasiHarga::where('is_active', true)->latest('berlaku_mulai')->first() ?? KonfigurasiHarga::latest('berlaku_mulai')->first();


        $totalDistribusi = Distribusi::whereBetween('tanggal_distribusi', [$dari, $sampai])->sum('jumlah_distribusi');
        $chartLabels = [];
        $chartDatasets = [];
        $laporanData = collect();
        $komoditasList = [];
        $totalPanen = 0;
        $totalStok = 0;
        $lowStockCount = 0;
        $totalGudang = 0;


        if ($jenis === 'stok') {
            $dateColumn = Schema::hasColumn('stok_beras', 'tanggal_update')
                ? 'tanggal_update'
                : (Schema::hasColumn('stok_beras', 'tanggal') ? 'tanggal' : 'created_at');

            $komoditasList = Stok::when(Schema::hasColumn('stok_beras', 'status'), fn($query) => $query->where('status', 'aktif'))
                ->distinct()->pluck('komoditas')->filter()->values()->toArray();
            $stokQuery = Stok::with('gudang')
                ->when(Schema::hasColumn('stok_beras', 'status'), fn($query) => $query->where('status', 'aktif'))
                ->whereDate($dateColumn, '>=', $dari)
                ->whereDate($dateColumn, '<=', $sampai);

            if ($komoditas) {
                $stokQuery->where('komoditas', $komoditas);
            }

            $allStokData = $stokQuery->get();

$laporanData = $allStokData
    ->groupBy('komoditas')
    ->map(fn($items) => $items->sortByDesc('created_at')->first())
    ->values();
            // Ambil batas minimum dari konfigurasi alert (bukan dari kolom stok_beras)
            $alertConfig = AlertConfiguration::first();
            $batasMinBeras = $alertConfig->batas_min_beras ?? 1000;
            $batasMinGabah = $alertConfig->batas_min_gabah ?? 500;

            // Inject batas_minimum yang benar ke setiap row
            $laporanData = $laporanData->map(function($item) use ($batasMinBeras, $batasMinGabah) {
                $komoditas = strtolower($item->komoditas ?? '');
                $item->batas_minimum = str_contains($komoditas, 'gabah') ? $batasMinGabah : $batasMinBeras;
                return $item;
            });

            $totalStok = $laporanData->sum('jumlah_stok');
            $totalGudang = $laporanData->pluck('gudang.nama_gudang')->unique()->filter()->count();
            $lowStockCount = $laporanData->filter(function($item) {
                return $item->jumlah_stok < $item->batas_minimum;
            })->count();

            $stockByDate = $allStokData->groupBy(function($item) use ($dateColumn) {
    return optional($item->{$dateColumn})->format('Y-m-d');
});
            $chartLabels = $stockByDate->keys()->toArray();
            $chartDatasets = [
    [
        'label' => 'Jumlah Stok (kg)',
        'data' => $stockByDate->map(function($items) {
            return $items->sortByDesc('created_at')->first()->saldo_setelah ?? $items->sortByDesc('created_at')->first()->jumlah_stok;
        })->values()->toArray(),
        'backgroundColor' => 'rgba(56,161,105,.75)',
    ]
];
        } else {
            $panenQuery = Panen::with(['petani','lahan'])->whereBetween('tanggal_panen', [$dari, $sampai]);
            if ($petaniId) {
                $panenQuery->whereHas('petani', function($q) use ($petaniId) {
                    $q->where('petani.id', $petaniId);
                });
            }
            $laporanData = $panenQuery->get();
            $totalPanen = $laporanData->sum('jumlah_gabah');
            $chartLabels = $laporanData->groupBy(function($item) {
                return optional($item->tanggal_panen)->format('M');
            })->keys()->toArray();
            $chartDatasets = [
                [
                    'label' => $jenis === 'margin' ? 'Gabah / Bulan' : 'Gabah Masuk (kg)',
                    'data' => $laporanData->groupBy(fn($item) => optional($item->tanggal_panen)->format('M'))->map(fn($items) => $items->sum('jumlah_gabah'))->values()->toArray(),
                    'backgroundColor' => 'rgba(245,158,11,.75)',
                ],
            ];

            $laporanData = $laporanData->transform(function($row) {
                $row->tonase_gabah = $row->jumlah_gabah;
                $row->beras_dihasilkan = $row->konversi_beras;
                $row->status = $row->status ?? 'selesai';
                return $row;
            });
        }

        return view('admin.laporan.index', compact(
            'petanis',
            'laporanData',
            'totalPanen',
            'totalDistribusi',
            'totalStok',
            'lowStockCount',
            'totalGudang',
            'chartLabels',
            'chartDatasets',
            'jenis',
            'komoditas',
            'komoditasList'
        ));
    }

    public function create()
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function store(Request $request)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function show($id)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function edit($id)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function update(Request $request, $id)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function destroy($id)
    {
        throw new HttpException(405, 'Method not allowed');
    }

    public function export()
    {
        $jenis = request('jenis', 'stok');
        $format = request('format','csv');
        $dari = request('dari', date('Y-m-01'));
        $sampai = request('sampai', date('Y-m-d'));
        $petaniId = request('petani_id');
        $komoditas = request('komoditas');

        if ($jenis === 'stok') {
            $dateColumn = Schema::hasColumn('stok_beras', 'tanggal_update')
                ? 'tanggal_update'
                : (Schema::hasColumn('stok_beras', 'tanggal') ? 'tanggal' : 'created_at');

            $items = Stok::with('gudang')
                ->when(Schema::hasColumn('stok_beras', 'status'), fn($query) => $query->where('status', 'aktif'))
                ->whereDate($dateColumn, '>=', $dari)
                ->whereDate($dateColumn, '<=', $sampai);
            if ($komoditas) {
                $items->where('komoditas', $komoditas);
            }
            $items = $items->get()
    ->groupBy('komoditas')
    ->map(fn($g) => $g->sortByDesc('created_at')->first())
    ->values();

            // Inject batas_minimum dari konfigurasi alert
            $alertConfig = AlertConfiguration::first();
            $batasMinBeras = $alertConfig->batas_min_beras ?? 1000;
            $batasMinGabah = $alertConfig->batas_min_gabah ?? 500;
            $items = $items->map(function($item) use ($batasMinBeras, $batasMinGabah) {
                $komoditas = strtolower($item->komoditas ?? '');
                $item->batas_minimum = str_contains($komoditas, 'gabah') ? $batasMinGabah : $batasMinBeras;
                return $item;
            });
        } else {
            
            $panenQuery = Panen::with(['petani','lahan'])->whereBetween('tanggal_panen', [$dari, $sampai]);
            if ($petaniId) {
                $panenQuery->where('petani_id', $petaniId);
            }
            $items = $panenQuery->get();
        }

        $petaniLabel = null;
        if ($petaniId && $jenis !== 'stok') {
            $petaniLabel = Petani::find($petaniId)?->nama ?? 'Petani tidak ditemukan';
        }

        if ($format === 'pdf') {
            try {
                $filename = 'laporan_' . date('Ymd_His') . '.pdf';
                $pdf = Pdf::loadView('admin.laporan.pdf', compact('items','jenis','dari','sampai','petaniLabel','komoditas'));
                return $pdf->download($filename);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'PDF export gagal. Pastikan barryvdh/laravel-dompdf telah terpasang.');
            }
        }

        if ($format === 'excel') {
            $filename = 'laporan_' . date('Ymd_His') . '.xls';
            $html = '<html><head><meta charset="UTF-8"></head><body>';
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:12px;width:100%;">';
            $html .= '<thead style="background:#f3f4f6;">';
            $html .= '<tr>';
            $html .= '<th>No</th>';

            if ($jenis === 'stok') {
                $html .= '<th>Gudang</th>';
                $html .= '<th>Komoditas</th>';
                $html .= '<th>Jumlah Stok (kg)</th>';
                $html .= '<th>Batas Minimum (kg)</th>';
                $html .= '<th>Tanggal Update</th>';
                $html .= '<th>Catatan</th>';
            } else {
                $html .= '<th>Petani</th>';
                $html .= '<th>Lahan</th>';
                $html .= '<th>Jumlah Gabah (kg)</th>';
                $html .= '<th>Tanggal Panen</th>';
            }

            $html .= '</tr></thead><tbody>';
            foreach ($items as $index => $item) {
                $html .= '<tr>';
                $html .= '<td>' . ($index + 1) . '</td>';
                if ($jenis === 'stok') {
                    $html .= '<td>' . e($item->gudang->nama_gudang ?? '-') . '</td>';
                    $html .= '<td>' . e($item->komoditas ?? '-') . '</td>';
                    $html .= '<td>' . number_format($item->jumlah_stok) . '</td>';
                    $html .= '<td>' . number_format($item->batas_minimum) . '</td>';
                    $html .= '<td>' . e(optional($item->tanggal_update)->format('Y-m-d H:i:s') ?? '-') . '</td>';
                    $html .= '<td>' . e($item->catatan ?? '-') . '</td>';
                } else {
                    $html .= '<td>' . e($item->petani->nama ?? '-') . '</td>';
                    $html .= '<td>' . e($item->lahan->nama ?? '-') . '</td>';
                    $html .= '<td>' . number_format($item->jumlah_gabah) . '</td>';
                    $html .= '<td>' . e(optional($item->tanggal_panen)->format('Y-m-d') ?? '-') . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></body></html>';
            return response($html)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', "attachment; filename={$filename}");
        }

        $csv = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $csv .= 'No,';

        if ($jenis === 'stok') {
            $csv .= 'Gudang,Komoditas,Jumlah Stok (kg),Batas Minimum (kg),Tanggal Update,Catatan\n';
            foreach ($items as $index => $item) {
                fputcsv($out = fopen('php://temp', 'r+'), [
                    $index + 1,
                    $item->gudang->nama_gudang ?? '-',
                    $item->komoditas ?? '-',
                    $item->jumlah_stok,
                    $item->batas_minimum,
                    optional($item->tanggal_update)->format('Y-m-d H:i:s'),
                    $item->catatan,
                ]);
                rewind($out);
                $csv .= stream_get_contents($out);
                fclose($out);
            }
        } else {
            $csv .= 'Petani,Lahan,Jumlah Gabah (kg),Konversi (%),Beras (kg),Tanggal Panen\n';
            foreach ($items as $index => $item) {
                $konv = $item->konversi_beras ?: '';
                $beras = $konv ? round($item->jumlah_gabah * ($konv / 100)) : '';
                $out = fopen('php://temp', 'r+');
                fputcsv($out, [
                    $index + 1,
                    $item->petani->nama ?? '-',
                    $item->lahan->nama ?? '-',
                    $item->jumlah_gabah,
                    $konv,
                    $beras,
                    optional($item->tanggal_panen)->format('Y-m-d'),
                ]);
                rewind($out);
                $csv .= stream_get_contents($out);
                fclose($out);
            }
        }

        $filename = 'laporan_' . date('Ymd_His') . '.csv';
        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
}