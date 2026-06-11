<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('id')) {
            return redirect('/');
        }
        if (session()->has('last_activity')) {

            if (time() - session('last_activity') > 3600) {

                session()->flush();

                return redirect('/');
            }
        }

        session(['last_activity' => time()]);

        return $next($request);
    }
}
