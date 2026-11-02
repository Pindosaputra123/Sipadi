<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Petani;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Memproses login pengguna.
     */
    public function login(Request $request)
    {
        // Validasi input login
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $identifier = $request->input('identifier');

        // Mencari user berdasarkan email atau nama
        $userQuery = User::query();

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {

            $userQuery->where('email', $identifier);

        } else {

            $lowerIdentifier = mb_strtolower($identifier, 'UTF-8');

            $userQuery->where(function ($query) use (
                $identifier,
                $lowerIdentifier
            ) {
                $query->where('name', $identifier)
                    ->orWhereRaw('LOWER(name) = ?', [$lowerIdentifier])
                    ->orWhereHas('petani', function ($query) use (
                        $identifier,
                        $lowerIdentifier
                    ) {
                        $query->where('nama', $identifier)
                            ->orWhereRaw(
                                'LOWER(nama) = ?',
                                [$lowerIdentifier]
                            );
                    });
            });
        }

        $user = $userQuery->first();

        // Periksa email/nama dan password
        if (
            !$user ||
            !Auth::attempt([
                'email' => $user->email,
                'password' => $request->input('password')
            ], $request->boolean('remember'))
        ) {
            throw ValidationException::withMessages([
                'identifier' => [
                    'Email/nama atau password salah.'
                ],
            ]);
        }

        // Ambil user yang sudah berhasil login
        $authenticatedUser = Auth::user();

        // ==========================================
        // PEMERIKSAAN STATUS AKUN PETANI
        // ==========================================

        if ($authenticatedUser->role === 'petani') {

            $petani = Petani::find($authenticatedUser->petani_id);

            // Jika data petani tidak ditemukan atau nonaktif
            if (
                !$petani ||
                strtolower(trim($petani->status ?? '')) !== 'aktif'
            ) {

                // Batalkan proses login
                Auth::logout();

                // Hapus session login
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'identifier' => [
                        'Akun Anda belum diaktifkan oleh admin. Silakan hubungi admin untuk aktivasi.'
                    ],
                ]);
            }
        }

        // ==========================================
        // LOGIN BERHASIL
        // ==========================================

        $request->session()->regenerate();

        return redirect()->intended(
            $this->redirectToByRole($authenticatedUser)
        );
    }

    /**
     * Mengarahkan pengguna berdasarkan role.
     */
    protected function redirectToByRole($user)
    {
        if ($user->role === 'petani') {
            return route('petani.dashboard');
        }

        if ($user->role === 'petugas') {
            return route('petugas.dashboard');
        }

        if ($user->role === 'admin') {
            return route('admin.dashboard');
        }

        return route('login');
    }

    /**
     * Logout pengguna.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
