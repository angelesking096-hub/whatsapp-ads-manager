<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->is_platform_admin) {
            abort(403, 'Accès réservé aux super administrateurs de la plateforme.');
        }

        return $next($request);
    }
}
