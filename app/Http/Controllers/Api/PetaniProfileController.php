<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PetaniProfileController
 * ─────────────────────────────────────────────────────────
 * Endpoint khusus untuk user yang login dengan role "petani".
 * Semua data dikembalikan berdasarkan petani_id dari user yang sedang login.
 *
 * Tambahkan di routes/api.php:
 *   Route::middleware('auth:api')->prefix('petani-profile')->group(function () {
 *       Route::get('/', [PetaniProfileController::class, 'profile']);
 *       Route::get('/panen', [PetaniProfileController::class, 'panen']);
 *       Route::get('/panen/{id}', [PetaniProfileController::class, 'panenDetail']);
 *       Route::get('/ringkasan', [PetaniProfileController::class, 'ringkasan']);
 *   });
 */
class PetaniProfileController extends Controller
{
    /**
     * GET /api/petani-profile
     * Kembalikan data profil petani milik user yang sedang login.
     */
    public function profile()
    {
        $user   = Auth::user();
        $petani = $user->petani;

        if (! $petani) {
            return response()->json([
                'message' => 'Data petani tidak ditemukan untuk akun ini.',
            ], 404);
        }

        // Sertakan relasi lahan agar Flutter bisa hitung total lahan
        $petani->load('lahan');

        return response()->json($petani);
    }

    /**
     * GET /api/petani-profile/panen?page=1
     * Daftar panen milik petani yang login, diurutkan terbaru.
     */
    public function panen(Request $request)
    {
        $user   = Auth::user();
        $petani = $user->petani;

        if (! $petani) {
            return response()->json(['message' => 'Data petani tidak ditemukan.'], 404);
        }

        $panen = Panen::whereHas('lahan', function ($q) use ($petani) {
                $q->where('petani_id', $petani->id);
            })
            ->with('lahan')
            ->orderByDesc('tanggal_panen')
            ->paginate($request->get('per_page', 15));

        return response()->json($panen);
    }

    /**
     * GET /api/petani-profile/panen/{id}
     * Detail satu catatan panen — hanya boleh diakses pemiliknya.
     */
    public function panenDetail(int $id)
    {
        $user   = Auth::user();
        $petani = $user->petani;

        if (! $petani) {
            return response()->json(['message' => 'Data petani tidak ditemukan.'], 404);
        }

        $panen = Panen::with('lahan.petani')
            ->whereHas('lahan', function ($q) use ($petani) {
                $q->where('petani_id', $petani->id);
            })
            ->findOrFail($id);

        return response()->json($panen);
    }

    /**
     * GET /api/petani-profile/ringkasan
     * Ringkasan statistik untuk dashboard petani.
     */
    public function ringkasan()
    {
        $user   = Auth::user();
        $petani = $user->petani;

        if (! $petani) {
            return response()->json(['message' => 'Data petani tidak ditemukan.'], 404);
        }

        $petani->load('lahan');

        $panens = Panen::whereHas('lahan', function ($q) use ($petani) {
                $q->where('petani_id', $petani->id);
            })
            ->orderByDesc('tanggal_panen')
            ->get();

        $totalGabah    = $panens->sum('jumlah_gabah');
        $totalBeras    = $panens->sum('konversi_beras');
        $panenTerakhir = $panens->first();

        $config = \App\Models\KonfigurasiHarga::where('is_active', true)
            ->latest('berlaku_mulai')
            ->first();

        $hargaGabah = $config ? $config->harga_beli_gabah : 0;

        return response()->json([
            'petani'         => $petani,
            'total_lahan'    => $petani->lahan->count(),
            'total_panen'    => $panens->count(),
            'total_gabah_kg' => round($totalGabah, 2),
            'total_beras_kg' => round($totalBeras, 2),
            'panen_terakhir' => $panenTerakhir?->tanggal_panen,
            'harga_beli_gabah'=> $hargaGabah,
        ]);
    }
}
