<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esAdministrador()) {
            abort(403, 'Solo el administrador puede acceder a este modulo.');
        }

        return $next($request);
    }
}
