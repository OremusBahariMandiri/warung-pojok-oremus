<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendedRememberSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    protected int $lifetimeRemember = 60 * 24 * 7;

    public function handle(Request $request, Closure $next): Response
    {
        if($request->session()->get('remember_me') === true){
            config(['session.lifetime' => $this->lifetimeRemember]);
        }
        return $next($request);
    }
}