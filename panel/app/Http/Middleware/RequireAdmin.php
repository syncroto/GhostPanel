<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acesso restrito a administradores.'], 403);
            }
            abort(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
