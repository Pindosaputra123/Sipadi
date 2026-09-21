<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->input('identifier');
        $userQuery = User::query();

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $userQuery->where('email', $identifier);
        } else {
            $lowerIdentifier = mb_strtolower($identifier, 'UTF-8');
            $userQuery->where(function ($query) use ($identifier, $lowerIdentifier) {
                $query->where('name', $identifier)
                      ->orWhereRaw('LOWER(name) = ?', [$lowerIdentifier])
                      ->orWhereHas('petani', function ($query) use ($identifier, $lowerIdentifier) {
                          $query->where('nama', $identifier)
                                ->orWhereRaw('LOWER(nama) = ?', [$lowerIdentifier]);
                      });
            });
        }

        $user = $userQuery->first();

        if (!$user || !Auth::attempt(['email' => $user->email, 'password' => $request->input('password')], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'identifier' => [trans('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectToByRole(Auth::user()));
    }

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
     * Log the current user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}