<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class JwtSessionGuest
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->session()->get('jwt_token');

        if ($token) {
            try {
                $user = JWTAuth::setToken($token)->authenticate();
                if ($user) {
                    auth()->setUser($user);
                }
            } catch (JWTException) {
                $request->session()->forget('jwt_token');
            }
        }

        return $next($request);
    }
}
