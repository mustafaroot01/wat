<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if (!$admin || !$admin->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المورد متاح فقط للمشرف الرئيسي.',
            ], 403);
        }

        return $next($request);
    }
}
