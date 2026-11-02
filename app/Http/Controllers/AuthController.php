<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Petani;
use App\Models\Lahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    // ==========================================
    // REGISTER USER
    // ==========================================
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'nullable|string|in:admin,petugas',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'petugas',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }


    // ==========================================
    // REGISTER PETANI
    // ==========================================
    public function registerPetani(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:users,email',
            'password'    => 'required|string|min:6|confirmed',
            'alamat'      => 'required|string',
            'telepon'     => 'nullable|string|max:50',
            'luas_lahan'  => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (Petani::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Email sudah terdaftar sebagai petani.',
                'errors'  => [
                    'email' => ['Email sudah terdaftar sebagai petani.']
                ],
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request) {

                // Membuat data petani dengan status nonaktif
                $petani = Petani::create([
                    'nama'       => $request->name,
                    'email'      => $request->email,
                    'alamat'     => $request->alamat,
                    'telepon'    => $request->telepon,
                    'luas_lahan' => $request->luas_lahan,
                    'komoditas'  => 'Padi',
                    'status'     => 'nonaktif',
                ]);

                // Membuat lahan jika luas lahan tersedia
                if ($request->filled('luas_lahan') && $request->luas_lahan > 0) {
                    Lahan::create([
                        'petani_id'  => $petani->id,
                        'nama_lahan' => 'Lahan utama',
                        'luas'       => $request->luas_lahan,
                        'lokasi'     => $request->alamat,
                        'status'     => 'aktif',
                    ]);
                }

                // Membuat akun user petani
                $user = User::create([
                    'name'      => $request->name,
                    'email'     => $request->email,
                    'password'  => Hash::make($request->password),
                    'role'      => 'petani',
                    'petani_id' => $petani->id,
                ]);

                return [
                    'petani' => $petani,
                    'user' => $user
                ];
            });

            return response()->json([
                'message' => 'Pendaftaran berhasil. Akun Anda menunggu aktivasi admin.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Pendaftaran gagal. Silakan coba kembali.',
            ], 500);
        }
    }


    // ==========================================
    // LOGIN
    // ==========================================
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email dan password wajib diisi.',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        try {

            // Memeriksa email dan password
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'login_gagal',
                    'message' => 'Email atau password salah.'
                ], 401);
            }

            $user = JWTAuth::user();

            // ==================================
            // PEMERIKSAAN STATUS AKUN PETANI
            // ==================================
            if ($user->role === 'petani') {

                $petani = Petani::find($user->petani_id);

                // Tolak jika data petani tidak ditemukan
                // atau status belum aktif
                if (!$petani || strtolower(trim($petani->status)) !== 'aktif') {

                    JWTAuth::setToken($token)->invalidate();

                    return response()->json([
                        'error' => 'akun_nonaktif',
                        'message' => 'Akun Anda belum diaktifkan oleh admin. Silakan hubungi admin.'
                    ], 403);
                }
            }

            // Jika lolos semua pemeriksaan
            return response()->json([
                'message' => 'Login berhasil.',
                'user'    => $user,
                'token'   => $token,
            ], 200);

        } catch (JWTException $e) {

            return response()->json([
                'error' => 'token_error',
                'message' => 'Tidak dapat membuat token autentikasi.'
            ], 500);
        }
    }


    // ==========================================
    // LOGOUT
    // ==========================================
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Berhasil logout.'
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Logout gagal.'
            ], 500);
        }
    }


    // ==========================================
    // GET USER PROFILE
    // ==========================================
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return response()->json($user);

        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kedaluwarsa.'
            ], 401);
        }
    }


    // ==========================================
    // REFRESH TOKEN
    // ==========================================
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'token' => $newToken
            ]);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'error' => 'Token kedaluwarsa dan tidak dapat diperbarui.'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Tidak dapat memperbarui token.'
            ], 500);
        }
    }
}
