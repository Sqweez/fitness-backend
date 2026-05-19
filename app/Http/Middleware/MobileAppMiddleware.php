<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Auth;
use Closure;
use Illuminate\Http\Request;

class MobileAppMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /*if ($request->hasHeader('T-Authorization')) {
            $token = $request->header('T-Authorization', null);
            $userId = base64_decode($token);
            $client = Client::whereKey($userId)->first();
            if ($client) {
                auth()->loginUsingId($userId);
            }
        }*/
        return $next($request);
    }
}
