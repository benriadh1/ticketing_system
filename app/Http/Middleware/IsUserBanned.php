<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class IsUserBanned
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->banned === "1") {
            $message = 'Votre compte a été définitivement banni.';

            if ($user->banned_at && now()->lessThan($user->banned_at)) {
                $banned_days = now()->diffInDays($user->banned_at) + 1;
                $message = 'Votre compte a été suspendu pour ' . $banned_days . ' ' . Str::plural('jour', $banned_days);
            }

            $user->token()->revoke();
            return response()->json(['error' => $message], 400);
        }

        return $next($request);
    }
}
