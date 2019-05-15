<?php

namespace App\Http\Middleware;

use Closure;

class Admin
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
        if(auth()->user()->admin == 1) {
            return $next($request);
        } else {
            return response()->json(['message' => 'You\'re not authorized to access this route', 'status' => 401], 401);
        }
    }
}
