<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Petani;
use Illuminate\Http\Request;
use App\Models\Lahan;
use Barryvdh\DomPDF\Facade\Pdf;

class PetaniController extends Controller
{
    public function index()
    {
        $petani = Petani::paginate(5);
        return view('admin.petani.index', compact('petani'));
    }

    public function create()
    {
        if (auth()->user()->role === 'petugas') abort(403, 'Unauthorized action.');
        return view('admin.petani.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'petugas') abort(403, 'Unauthorized action.');

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'telepon' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:50',
            'email' => 'required|email|max:255|unique:petani,email|unique:users,email',
            'status' => 'required|string|in:aktif,nonaktif',
            'luas_lahan' => 'nullable|integer|min:0',
            'komoditas' => 'required|string',
            'catatan' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $petani = Petani::create([
            'nama' => $data['nama'],
            'alamat' => $data['alamat'],
            'telepon' => $data['telepon'],
            'no_hp' => $data['no_hp'],
            'email' => $data['email'],
            'status' => $data['status'],
            'luas_lahan' => $data['luas_lahan'],
            'komoditas' => $data['komoditas'],
            'catatan' => $data['catatan'],
        ]);

        // Jika user mengisi luas_lahan pada form Petani, buatkan juga entri Lahan
        // supaya fitur yang mengandalkan relasi lahan (mis. pencatatan panen) bekerja.
        if (! empty($data['luas_lahan']) && $data['luas_lahan'] > 0) {
            Lahan::create([
                'petani_id' => $petani->id,
                'nama_lahan' => 'Lahan utama',
                'luas' => $data['luas_lahan'],
                'lokasi' => $data['alamat'] ?? null,
                'status' => 'aktif',
            ]);
        }

        // Otomatis buatkan User untuk petani ini di Manajemen Pengguna
        \App\Models\User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => 'petani',
            'petani_id' => $petani->id,
        ]);

        return redirect()->route('admin.petani.index')->with('success', 'Petani dan Akun Pengguna berhasil ditambahkan');
    }

    public function show(Petani $petani)
    {
        if (auth()->user()->role === 'petugas') abort(403, 'Unauthorized action.');
        $petani->load('lahan');
        return view('admin.petani.show', compact('petani'));
    }

    public function edit(Petani $petani)
    {
        if (auth()->user()->role === 'petugas') abort(403, 'Unauthorized action.');
        return view('admin.petani.edit', compact('petani'));
    }

    public function update(Request $request, Petani $petani)
    {
        if (auth()->user()->role === 'petugas') abort(403, 'Unauthorized action.');

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'telepon' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:petani,email,' . $petani->id,
            'status' => 'required|string|in:aktif,non-aktif,nonaktif',
            'luas_lahan' => 'nullable|integer|min:0',
            'komoditas' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        if ($data['status'] === 'non-aktif') {
            $data['status'] = 'nonaktif';
        }

        $petani->update($data);

        return redirect()->route('admin.petani.index')->with('success', 'Petani berhasil diperbarui');
    }

    public function destroy(Petani $petani)
    {
        if (auth()->user()->role === 'petugas') abort(403, 'Unauthorized action.');
        $petani->delete();
        return redirect()->route('admin.petani.index')->with('success', 'Petani berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $petani = Petani::findOrFail($id);
        $petani->status = $petani->status === 'aktif' ? 'nonaktif' : 'aktif';
        $petani->save();

        return response()->json([
            'success' => true,
            'status' => $petani->status,
            'message' => 'Status berhasil diubah',
        ]);
    }

    public function export()
    {
        $format = request('format', 'csv');
        $petani = Petani::orderBy('nama', 'asc')->get();

        if ($format === 'pdf') {
            try {
                $filename = 'petani_' . date('Ymd_His') . '.pdf';
                $pdf = Pdf::loadView('admin.petani.pdf', compact('petani'));
                return $pdf->download($filename);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'PDF export gagal. Pastikan barryvdh/laravel-dompdf telah terpasang.');
            }
        }

        if ($format === 'excel') {
            $filename = 'petani_' . date('Ymd_His') . '.xls';
            $html = '<table border="1"><thead><tr>' .
                '<th>No</th>' .
                '<th>Nama Petani</th>' .
                '<th>No. Telepon/HP</th>' .
                '<th>Email</th>' .
                '<th>Luas Lahan (m²)</th>' .
                '<th>Komoditas</th>' .
                '<th>Status</th>' .
                '<th>Alamat</th>' .
                '</tr></thead><tbody>';

            foreach ($petani as $index => $p) {
                $html .= '<tr>' .
                    '<td>' . ($index + 1) . '</td>' .
                    '<td>' . e($p->nama) . '</td>' .
                    '<td>' . e($p->telepon ?? '-') . '</td>' .
                    '<td>' . e($p->email ?? '-') . '</td>' .
                    '<td>' . ($p->luas_lahan !== null ? number_format($p->luas_lahan) : '-') . '</td>' .
                    '<td>' . e($p->komoditas) . '</td>' .
                    '<td>' . e($p->status === 'nonaktif' ? 'Non-aktif' : ucfirst($p->status)) . '</td>' .
                    '<td>' . e($p->alamat ?? '-') . '</td>' .
                    '</tr>';
            }

            $html .= '</tbody></table>';
            return response($html)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', "attachment; filename={$filename}");
        }

        $csv = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $csv .= "No,Nama Petani,No. Telepon/HP,Email,Luas Lahan (m²),Komoditas,Status,Alamat\n";
        foreach ($petani as $index => $p) {
            $csv .= implode(',', [
                $index + 1,
                '"' . str_replace('"', '""', $p->nama) . '"',
                '"' . str_replace('"', '""', $p->telepon ?? '-') . '"',
                '"' . str_replace('"', '""', $p->email ?? '-') . '"',
                '"' . ($p->luas_lahan !== null ? number_format($p->luas_lahan) : '-') . '"',
                '"' . str_replace('"', '""', $p->komoditas) . '"',
                '"' . str_replace('"', '""', $p->status === 'nonaktif' ? 'Non-aktif' : ucfirst($p->status)) . '"',
                '"' . str_replace('"', '""', $p->alamat ?? '-') . '"',
            ]) . "\n";
        }

        $filename = 'petani_' . date('Ymd_His') . '.csv';
        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
}