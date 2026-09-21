@extends('layout.admin')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola akun pengguna sistem')

@section('content')

@if(session('success'))
    <div class="alert-banner success" style="margin-bottom: 24px;">
        <i class="fas fa-check-circle"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="alert-banner danger" style="margin-bottom: 24px;">
        <i class="fas fa-exclamation-circle"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Daftar Pengguna</div>
            <div class="card-subtitle">Semua pengguna terdaftar dalam sistem</div>
        </div>
        <a href="{{ route('admin.pengguna.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Pengguna
        </a>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge badge-green">Admin</span>
                            @elseif($user->role === 'petugas')
                                <span class="badge badge-blue">Petugas</span>
                            @else
                                <span class="badge badge-gray">{{ ucfirst($user->role) }}</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            <a href="{{ route('admin.pengguna.edit', $user->id) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding: 24px;">Belum ada pengguna terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($users->hasPages())
        <div style="padding:16px;display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:10px;border-top:1px solid var(--border,#e2e8f0);">
            <div style="width:100%;text-align:center;font-size:12px;color:var(--text-muted);">
                Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} pengguna
            </div>
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:4px;">
                @if($users->onFirstPage())
                    <span style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-muted);font-size:12px;cursor:not-allowed;">First</span>
                    <span style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-muted);font-size:12px;cursor:not-allowed;"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $users->url(1) }}" style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-primary);font-size:12px;text-decoration:none;">First</a>
                    <a href="{{ $users->previousPageUrl() }}" style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-primary);font-size:12px;text-decoration:none;"><i class="fas fa-chevron-left"></i></a>
                @endif
                @foreach($users->getUrlRange(max(1,$users->currentPage()-2), min($users->lastPage(),$users->currentPage()+2)) as $page => $url)
                    <a href="{{ $url }}" style="padding:6px 10px;border-radius:6px;font-size:12px;text-decoration:none;background:{{ $page == $users->currentPage() ? 'var(--green-600)' : 'var(--surface-3)' }};color:{{ $page == $users->currentPage() ? 'white' : 'var(--text-primary)' }};">{{ $page }}</a>
                @endforeach
                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-primary);font-size:12px;text-decoration:none;"><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ $users->url($users->lastPage()) }}" style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-primary);font-size:12px;text-decoration:none;">Last</a>
                @else
                    <span style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-muted);font-size:12px;cursor:not-allowed;"><i class="fas fa-chevron-right"></i></span>
                    <span style="padding:6px 10px;min-width:66px;text-align:center;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:var(--surface-3);color:var(--text-muted);font-size:12px;cursor:not-allowed;">Last</span>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal-overlay" id="modalHapus">
    <div class="modal" style="max-width: 400px; text-align: center;">
        <div style="font-size: 48px; color: var(--red-500); margin-bottom: 15px; margin-top: 15px;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 style="margin-bottom: 10px;">Konfirmasi Hapus</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Apakah Anda yakin ingin menghapus akun pengguna <strong id="hapusNamaPengguna"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
        
        <form method="POST" id="formHapusPengguna">
            @csrf @method('DELETE')
            <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 15px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalHapus')">Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(id, nama) {
    document.getElementById('hapusNamaPengguna').innerText = nama;
    const form = document.getElementById('formHapusPengguna');
    form.action = `{{ url('admin/pengguna') }}/${id}`;
    openModal('modalHapus');
}
</script>
@endpush