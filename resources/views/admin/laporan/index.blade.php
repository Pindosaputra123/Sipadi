{{-- ============================================================
     LAPORAN
     ============================================================ --}}
@extends('layout.admin')

@section('title', 'Laporan')
@section('page-title', 'Laporan')
@php
    $jenis = $jenis ?? request('jenis', 'stok');
    $komoditas = $komoditas ?? request('komoditas');
    $subtitle = match($jenis) {
        'panen' => 'Rekapitulasi panen per periode',
        'stok' => $komoditas ? "Rekapitulasi stok gudang {$komoditas} per periode" : 'Rekapitulasi stok gudang per periode',
        default => 'Rekapitulasi stok gudang per periode',
    };
@endphp
@section('page-subtitle', $subtitle)

@section('content')

{{-- FILTER --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:20px 24px;">
        <form method="GET" action="{{ route('admin.laporan.index') }}" id="laporanFilterForm">
            <div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
                <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                    <label>Jenis Laporan</label>
                    <select name="jenis" id="jenisLaporan" onchange="updateReportForm()">
                        <option value="stok" {{ request('jenis','stok')=='stok'?'selected':'' }}>Laporan Stok</option>
                        <option value="panen" {{ request('jenis')=='panen'?'selected':'' }}>Laporan Panen</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:130px;" id="komoditasFilterWrapper">
                    <label>Komoditas</label>
                    <select name="komoditas" id="komoditas" onchange="document.getElementById('laporanFilterForm').submit()">
                        <option value="">Semua Komoditas</option>
                        @foreach($komoditasList ?? [] as $k)
                            <option value="{{ $k }}" {{ request('komoditas')==$k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:130px;" id="petaniFilterWrapper">
                    <label>Petani</label>
                    <select name="petani_id" id="petani_id" onchange="document.getElementById('laporanFilterForm').submit()">
                        <option value="">Semua Petani</option>
                        @foreach($petanis ?? [] as $p)
                            <option value="{{ $p->id }}" {{ request('petani_id')==$p->id?'selected':'' }}>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:130px;">
                    <label>Dari Tanggal</label>
                    <input type="date" name="dari" value="{{ request('dari', date('Y-m-01')) }}">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:130px;">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="sampai" value="{{ request('sampai', date('Y-m-d')) }}">
                </div>
                <button type="submit" class="btn btn-primary" style="flex-shrink:0;">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="{{ route('admin.laporan.export', array_merge(request()->all(), ['format'=>'pdf'])) }}" class="btn btn-secondary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('admin.laporan.export', array_merge(request()->all(), ['format'=>'excel'])) }}" class="btn btn-secondary">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- RINGKASAN --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    @if($jenis === 'stok')
        <div class="card" style="border-top:3px solid var(--green-500);">
            <div class="card-body" style="text-align:center;">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">Total Stok</div>
                <div style="font-size:28px;font-weight:800;">{{ number_format($totalStok ?? 0) }} kg</div>
                <div style="font-size:12px;color:var(--text-muted);">{{ $komoditas ? 'Stok ' . strtolower($komoditas) . ' periode ini' : 'Semua komoditas periode ini' }}</div>
            </div>
        </div>
        <div class="card" style="border-top:3px solid var(--blue-500);">
            <div class="card-body" style="text-align:center;">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">Gudang Terdata</div>
                <div style="font-size:28px;font-weight:800;">{{ number_format($totalGudang ?? 0) }}</div>
                <div style="font-size:12px;color:var(--text-muted);">lokasi stok aktif</div>
            </div>
        </div>
        <div class="card" style="border-top:3px solid var(--amber-500);">
            <div class="card-body" style="text-align:center;">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">Stok Kurang</div>
                <div style="font-size:28px;font-weight:800;">{{ number_format($lowStockCount ?? 0) }}</div>
                <div style="font-size:12px;color:var(--text-muted);">entry di bawah batas minimum</div>
            </div>
        </div>
    @elseif($jenis === 'panen')
        <div class="card" style="border-top:3px solid var(--green-500);">
            <div class="card-body" style="text-align:center;">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">Total Panen</div>
                <div style="font-size:28px;font-weight:800;">{{ number_format($totalPanen ?? 0) }} kg</div>
                <div style="font-size:12px;color:var(--text-muted);">gabah periode ini</div>
            </div>
        </div>

    @endif
</div>

{{-- GRAFIK --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div class="card-title">
            @if($jenis === 'stok')
                Grafik Stok Periode ini
            @elseif($jenis === 'panen')
                Grafik Panen per Bulan
            @else
                Grafik Estimasi Margin
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="chart-wrap" style="height:280px;">
            <canvas id="chartLaporan"></canvas>
        </div>
    </div>
</div>

{{-- TABEL DETAIL --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">
            @if($jenis === 'stok')
                Detail Laporan Stok Beras
            @elseif($jenis === 'panen')
                Detail Laporan Panen per Petani
            @else
                Detail Laporan Margin per Panen
            @endif
        </div>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                @if($jenis === 'stok')
                    <tr>
                        <th>Gudang</th>
                        <th>Komoditas</th>
                        <th>Jumlah Stok</th>
                        <th>Batas Minimum</th>
                        <th>Tanggal Update</th>
                        <th>Status</th>
                    </tr>
                @else
                    <tr>
                        <th>Petani</th>
                        <th>Lahan (m²)</th>
                        <th>Hasil Gabah</th>
                        <th>Tanggal Panen</th>
                        @if($jenis === 'margin')
                            <th>Status</th>
                        @endif
                    </tr>
                @endif
            </thead>
            <tbody>
                @forelse($laporanData ?? [] as $row)
                    @if($jenis === 'stok')
                        <tr>
                            <td><strong>{{ $row->gudang->nama_gudang ?? '-' }}</strong></td>
                            <td><strong>{{ $row->komoditas ?? '-' }}</strong></td>
                            <td><strong>{{ number_format($row->jumlah_stok) }} kg</strong></td>
                            <td>{{ number_format($row->batas_minimum) }} kg</td>
                            <td style="font-size:12.5px;">{{ optional($row->tanggal_update ?? $row->tanggal)->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <span class="badge badge-{{ $row->jumlah_stok < $row->batas_minimum ? 'amber' : 'green' }}">
                                    {{ $row->jumlah_stok < $row->batas_minimum ? 'Kurang' : 'Cukup' }}
                                </span>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td><strong>{{ $row->petani->nama ?? '-' }}</strong></td>
                            <td>{{ number_format(optional($row->petani)->luas_lahan ?? 0) }}</td>
                            <td><strong>{{ number_format($row->jumlah_gabah ?? 0) }} kg</strong></td>
                            <td style="font-size:12.5px;">{{ optional($row->tanggal_panen)->format('Y-m-d') }}</td>
                            @if($jenis === 'margin')
                                <td><span class="badge badge-{{ ($row->status ?? 'selesai') == 'selesai' ? 'green' : 'amber' }}">{{ $row->status ?? 'selesai' }}</span></td>
                            @endif
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $jenis === 'stok' ? 6 : ($jenis === 'margin' ? 5 : 4) }}" class="text-center text-muted" style="padding:24px;">Belum ada data untuk jenis laporan ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
function updateReportForm() {
    const jenis = document.getElementById('jenisLaporan').value;
    const petaniWrapper = document.getElementById('petaniFilterWrapper');
    const petaniSelect = document.getElementById('petani_id');
    const komoditasWrapper = document.getElementById('komoditasFilterWrapper');
    const komoditasSelect = document.getElementById('komoditas');

    if (jenis === 'stok') {
        petaniWrapper.style.display = 'none';
        petaniSelect.disabled = true;
        komoditasWrapper.style.display = '';
        komoditasSelect.disabled = false;
    } else {
        petaniWrapper.style.display = '';
        petaniSelect.disabled = false;
        komoditasWrapper.style.display = 'none';
        komoditasSelect.disabled = true;
    }

    document.getElementById('laporanFilterForm').submit();
}

document.addEventListener('DOMContentLoaded', function () {
    const jenis = document.getElementById('jenisLaporan').value;
    const petaniWrapper = document.getElementById('petaniFilterWrapper');
    const petaniSelect = document.getElementById('petani_id');
    const komoditasWrapper = document.getElementById('komoditasFilterWrapper');
    const komoditasSelect = document.getElementById('komoditas');

    if (jenis === 'stok') {
        petaniWrapper.style.display = 'none';
        petaniSelect.disabled = true;
        komoditasWrapper.style.display = '';
        komoditasSelect.disabled = false;
    } else {
        petaniWrapper.style.display = '';
        petaniSelect.disabled = false;
        komoditasWrapper.style.display = 'none';
        komoditasSelect.disabled = true;
    }
});

const laporanChartData = {
    labels: @json($chartLabels),
    datasets: @json($chartDatasets)
};

new Chart(document.getElementById('chartLaporan').getContext('2d'), {
    type: 'bar',
    data: laporanChartData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
        }
    }
});
</script>
@endpush