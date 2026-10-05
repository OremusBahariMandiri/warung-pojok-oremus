<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SessionFingerprint
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('logout') || $request->is('login')) {
            return $next($request);
        }

        $userAgent = $request->header('User-Agent');
        $fingerprint = hash('sha256', $userAgent);

        if ($request->session()->has('last_fingerprint')) {
            if ($request->session()->get('last_fingerprint') !== $fingerprint) {
                $request->session()->flush(); 
                Auth::logout();               
                return redirect()->route('login')->with('error', 'Sesi dihanguskan demi keamanan karena terdeteksi berpindah perangkat.');
            }
        } 

        if (Auth::check()) {
            $request->session()->put('last_fingerprint', $fingerprint);
        }

        return $next($request);
    }
}