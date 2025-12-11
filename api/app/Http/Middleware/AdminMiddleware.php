<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o user está logado E se é tipo 'A'
        if ($request->user() && $request->user()->type === 'A') {
            return $next($request);
        }

        // Se não for admin, dá erro 403 (Proibido)
        return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
    }
}
