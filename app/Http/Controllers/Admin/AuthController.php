<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'sub_admin'])) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'sub_admin'])) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Access denied. Admins must login here.'),
            ]);
        }

        $request->session()->regenerate();

        ActivityLogger::log([
            'action_type' => 'login',
            'action_by' => $user->id,
            'target_table' => 'users',
            'action_on' => $user->id,
            'description' => "Admin logged in: {$user->email}",
        ], $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        ActivityLogger::log([
            'action_type' => 'logout',
            'action_by' => $user?->id,
            'target_table' => 'users',
            'action_on' => $user?->id,
            'description' => 'Admin logged out: '.($user?->email ?? 'unknown'),
        ], $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
