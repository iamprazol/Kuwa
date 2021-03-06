<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerifyUser
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
        if(Auth::user()->is_verified == 1) {
            return $next($request);
        } else {
            return response()->json(['message' => 'You\'re still not verified.', 'status' => 401], 401);
        }
    }
}
