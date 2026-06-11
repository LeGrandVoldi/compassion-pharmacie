<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next, $module)
    {
        if (session('role') === 'admin') {
            return $next($request);
        }

        $permissions = session('permissions', []);

        if (!isset($permissions[$module])) {
            abort(403);
        }

        $hasPermission = collect($permissions[$module])
            ->contains(fn ($value) => $value == 1);

        if (!$hasPermission) {
            abort(403);
        }

        return $next($request);
    }
}
