@extends('layout.admin')

@section('title', 'Manajemen Harga')
@section('page-title', 'Manajemen Harga')
@section('page-subtitle', 'Riwayat konfigurasi harga beli gabah, ongkos giling, dan harga jual beras')

@section('content')

@if(session('success'))
    <div style="background: var(--green-100); color: var(--green-700); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
        <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="background: var(--red-100); color: var(--red-700); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> {{ session('error') }}
    </div>
@endif


<div class="card" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div class="card-title">Riwayat Konfigurasi Harga</div>
        <a href="{{ route('admin.harga.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Konfigurasi</a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal Berlaku</th>
                    <th>Harga Beli Gabah</th>
                    <th>Harga Jual Beras</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($konfigurasi as $k)
                    <tr>
                        <td style="font-size:13.5px;">{{ $k->berlaku_mulai->format('d M Y') }}</td>
                        <td>Rp {{ number_format($k->harga_beli_gabah, 0, ',', '.') }} /kg</td>
                        <td>Rp {{ number_format($k->harga_jual_beras, 0, ',', '.') }} /kg</td>
                        <td>
                            @if($k->is_active)
                                <span class="badge badge-green">Aktif</span>
                            @else
                                <span class="badge badge-gray" style="background:#e2e8f0;color:#475569;">Tidak Aktif</span>
                            @endif
                        </td>
                        <td style="display:flex; gap:8px;">
                            @if(!$k->is_active)
                            <form action="{{ route('admin.harga.activate', $k->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-secondary" title="Jadikan Aktif"><i class="fas fa-check"></i></button>
                            </form>
                            @endif
                            <a href="{{ route('admin.harga.edit', $k->id) }}" class="btn btn-sm btn-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                            @if(!$k->is_active)
                            <form action="{{ route('admin.harga.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Hapus konfigurasi ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-secondary" style="color:var(--red-500)" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                        <td colspan="5" class="text-center text-muted" style="padding:24px;">Belum ada konfigurasi harga.</td>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
