<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Bloqueia o acesso à rota /setup se já existir um admin cadastrado.
 * Redireciona para /setup se não existir nenhum admin E o usuário tentar acessar qualquer rota protegida.
 */
class CheckSetupComplete
{
    public function handle(Request $request, Closure $next)
    {
        $hasAdmin = User::exists();

        // Se já existe admin e está tentando acessar /setup → bloqueia
        if ($hasAdmin && $request->routeIs('setup.*')) {
            return redirect()->route('dashboard');
        }

        // Se não existe admin e NÃO está em /setup → redireciona para setup
        if (!$hasAdmin && !$request->routeIs('setup.*')) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
