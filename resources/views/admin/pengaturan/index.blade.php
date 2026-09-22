@extends('layout.admin')

@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Konfigurasi kapasitas gudang, target pasar, dan batas alert')

@section('content')

@if(session('success'))
    <div class="alert-banner success" style="margin-bottom:20px;">
        <i class="fas fa-check-circle"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

<form method="POST" action="{{ route('admin.pengaturan.update') }}">
    @csrf @method('PUT')

    <div class="grid-2" style="gap:24px;">
        {{-- KAPASITAS GUDANG --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-warehouse" style="color:var(--green-500);margin-right:8px;"></i> Kapasitas Gudang</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
                <div class="form-group">
                    <label>Kapasitas Maks Beras (kg) <span style="color:var(--red-500)">*</span></label>
                    <input type="number" name="kapasitas_max_beras" min="1" required
                        value="{{ old('kapasitas_max_beras', $config->kapasitas_max_beras ?? 1000) }}">
                    <div class="form-hint">Batas maksimum stok beras di gudang.</div>
                    @error('kapasitas_max_beras') <div style="color:var(--red-500);font-size:12px;">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Kapasitas Maks Gabah (kg) <span style="color:var(--red-500)">*</span></label>
                    <input type="number" name="kapasitas_max_gabah" min="1" required
                        value="{{ old('kapasitas_max_gabah', $config->kapasitas_max_gabah ?? 2000) }}">
                    <div class="form-hint">Batas maksimum stok gabah di gudang.</div>
                    @error('kapasitas_max_gabah') <div style="color:var(--red-500);font-size:12px;">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- TARGET PASAR --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-chart-line" style="color:var(--blue-500);margin-right:8px;"></i> Target Pasar</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
                <div class="form-group">
                    <label>Target Distribusi / Bulan (kg) <span style="color:var(--red-500)">*</span></label>
                    <input type="number" name="target_pasar" min="0" required
                        value="{{ old('target_pasar', $config->target_pasar ?? 9000) }}">
                    <div class="form-hint">Dipakai sebagai garis target pada grafik dashboard.</div>
                    @error('target_pasar') <div style="color:var(--red-500);font-size:12px;">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- BATAS ALERT --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-bell" style="color:var(--red-500);margin-right:8px;"></i> Batas Alert Stok</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
                <div class="form-group">
                    <label>Batas Minimum Beras (kg) <span style="color:var(--red-500)">*</span></label>
                    <input type="number" name="batas_min_beras" min="0" required
                        value="{{ old('batas_min_beras', $config->batas_min_beras ?? 100) }}">
                    <div class="form-hint">Alert aktif jika stok beras di bawah angka ini.</div>
                    @error('batas_min_beras') <div style="color:var(--red-500);font-size:12px;">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Batas Minimum Gabah (kg) <span style="color:var(--red-500)">*</span></label>
                    <input type="number" name="batas_min_gabah" min="0" required
                        value="{{ old('batas_min_gabah', $config->batas_min_gabah ?? 200) }}">
                    <div class="form-hint">Alert aktif jika stok gabah di bawah angka ini.</div>
                    @error('batas_min_gabah') <div style="color:var(--red-500);font-size:12px;">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- INFO SISTEM --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-info-circle" style="color:var(--text-muted);margin-right:8px;"></i> Informasi Sistem</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:12.5px;color:var(--text-muted);">Nama Sistem</label>
                    <input type="text" value="SIPADI" disabled>
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:12.5px;color:var(--text-muted);">Kelompok</label>
                    <input type="text" value="Goat Pensiun" disabled>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Semua Pengaturan
        </button>
    </div>
</form>

@endsection
