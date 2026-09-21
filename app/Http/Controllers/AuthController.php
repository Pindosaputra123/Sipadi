<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Petani;
use App\Models\Lahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'string|in:admin,petugas',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'petugas',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    /**
     * Register khusus untuk petani dari aplikasi mobile.
     * Membuat data petani (status nonaktif) + akun user (role petani).
     * Akun tidak bisa login sampai admin mengaktifkan status petani.
     */
    public function registerPetani(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Step 1
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            // Step 2
            'alamat'                => 'required|string',
            'telepon'               => 'nullable|string|max:50',
            'luas_lahan'            => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Cek duplikasi email di tabel petani
        if (Petani::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Email sudah terdaftar sebagai petani.',
                'errors'  => ['email' => ['Email sudah terdaftar sebagai petani.']],
            ], 422);
        }

        // Buat record Petani (status nonaktif — menunggu aktivasi admin)
        $petani = Petani::create([
            'nama'       => $request->name,
            'email'      => $request->email,
            'alamat'     => $request->alamat,
            'telepon'    => $request->telepon,
            'luas_lahan' => $request->luas_lahan,
            'komoditas'  => 'Padi',
            'status'     => 'nonaktif',
        ]);

        // Buat Lahan jika ada luas_lahan
        if (!empty($request->luas_lahan) && $request->luas_lahan > 0) {
            Lahan::create([
                'petani_id'   => $petani->id,
                'nama_lahan'  => 'Lahan utama',
                'luas'        => $request->luas_lahan,
                'lokasi'      => $request->alamat,
                'status'      => 'aktif',
            ]);
        }

        // Buat akun User dengan role petani (belum bisa login)
        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'petani',
            'petani_id' => $petani->id,
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil. Akun Anda sedang menunggu aktivasi oleh admin.',
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        $user = JWTAuth::user();

        // Jika petani, cek apakah statusnya sudah aktif
        if ($user->role === 'petani' && $user->petani_id) {
            $petani = Petani::find($user->petani_id);
            if ($petani && $petani->status !== 'aktif') {
                // Invalidate token langsung
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json([
                    'error' => 'akun_nonaktif',
                    'message' => 'Akun Anda belum diaktifkan oleh admin. Silakan hubungi admin untuk aktivasi.',
                ], 403);
            }
        }

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me()
    {
        return response()->json(JWTAuth::user());
    }

    /**
     * Refresh a JWT token (Flutter calls this when it gets 401).
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token' => $newToken]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired and can no longer be refreshed'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }
}

