<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class OptionalAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && $accessToken->tokenable) {
                Auth::setUser($accessToken->tokenable);
            }
        }

        return $next($request);
    }
}
