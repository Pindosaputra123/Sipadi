<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Petani;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(5);
        return view('admin.pengguna.index', compact('users'));
    }

    public function create()
    {
        $petaniList = Petani::orderBy('nama')->get();
        return view('admin.pengguna.create', compact('petaniList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(['admin', 'petugas', 'petani'])],
            'petani_id' => ['nullable', 'required_if:role,petani', 'integer', 'exists:petani,id', Rule::unique('users', 'petani_id')->where(function ($query) {
                return $query->where('role', 'petani');
            })],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $plainPassword = $validated['password'];

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'petani_id' => $validated['role'] === 'petani' ? $validated['petani_id'] : null,
            'password' => Hash::make($plainPassword),
        ]);

        return redirect()
            ->route('admin.pengguna.create')
            ->with([
                'success' => 'Pengguna berhasil dibuat.',
                'user_created' => true,
                'user_name' => $validated['name'],
                'user_email' => $validated['email'],
                'user_password' => $plainPassword,
            ]);
    }

    public function show($id)
    {
        return redirect()->route('admin.pengguna.index');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $petaniList = Petani::orderBy('nama')->get();
        return view('admin.pengguna.edit', compact('user', 'petaniList'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'petugas', 'petani'])],
            'petani_id' => ['nullable', 'required_if:role,petani', 'integer', 'exists:petani,id'],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->petani_id = $validated['role'] === 'petani' ? $validated['petani_id'] : null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.pengguna.index')
            ->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.pengguna.index')->with('error', 'Tidak dapat menghapus akun yang sedang Anda gunakan.');
        }

        $user->delete();

        return redirect()
            ->route('admin.pengguna.index')
            ->with('success', 'Akun pengguna berhasil dihapus.');
    }
}