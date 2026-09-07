<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // If specific roles are passed, verify user has one of them
        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin (' . implode('/', $roles) . ') untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
