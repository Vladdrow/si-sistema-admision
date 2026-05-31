<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->rol, $roles, true)) {
            abort(403, 'No tiene permiso para acceder a este modulo.');
        }

        return $next($request);
    }
}
