<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $token = Auth::guard('api')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);

        if (! $token) {
            return back()->withErrors(['username' => 'Invalid username or password.']);
        }

        $request->session()->put('jwt_token', $token);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $token = $request->session()->pull('jwt_token');
        if ($token) {
            try {
                JWTAuth::setToken($token)->invalidate();
            } catch (\Throwable) {
            }
        }

        $request->session()->flush();
        return redirect()->route('login');
    }
}
