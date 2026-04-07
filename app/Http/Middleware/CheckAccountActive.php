<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            // Delete current token to force logout
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'حسابك معطل حالياً، يرجى التواصل مع الإدارة.'
            ], 403);
        }

        return $next($request);
    }
}
