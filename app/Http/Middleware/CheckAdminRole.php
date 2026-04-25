<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!($user instanceof Admin)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحيات الوصول لهذه الصفحة.',
            ], 403);
        }

        if (!$user->is_active) {
            $user->currentAccessToken()?->delete();
            return response()->json([
                'success' => false,
                'message' => 'حساب الإدارة هذا معطل.',
            ], 403);
        }

        return $next($request);
    }
}
