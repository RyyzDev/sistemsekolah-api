<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login dan memiliki role admin
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request);
        }

        // Jika bukan admin, kembalikan response error
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Only admins can access this resource.'
        ], 403);
    }
}
