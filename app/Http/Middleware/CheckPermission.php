<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Usage: middleware('permission:products') or middleware('permission:orders,products')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = $request->user();

        // Super admin bypasses all permission checks
        if ($admin && $admin->is_super_admin) {
            return $next($request);
        }

        // Check if admin has at least one of the required permissions
        foreach ($permissions as $permission) {
            if ($admin && $admin->hasPermission($permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية للوصول لهذا المورد.',
        ], 403);
    }
}
