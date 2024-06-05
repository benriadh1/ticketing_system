<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;


use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;


class LastSeenUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user()) {
            $key = 'last_seen_'.$request->user()->id;
            if (!Cache::has($key)) {
                User::where('id', $request->user()->id)->update(['last_seen' => Carbon::now()]);
                Cache::put($key, true, Carbon::now()->addMinute());
            }
        }
        return $next($request);
    }
}
