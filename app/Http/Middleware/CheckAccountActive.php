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
        $user = $request->user();

        if ($user && $user->is_self_deleted) {
            $user->currentAccessToken()?->delete();
            return response()->json([
                'success' => false,
                'message' => 'تم حذف هذا الحساب بناءً على طلبك. تواصل مع الإدارة لاستعادته.',
                'code'    => 'account_deleted',
            ], 403);
        }

        if ($user && !$user->is_active) {
            $user->currentAccessToken()?->delete();
            return response()->json([
                'success' => false,
                'message' => 'حسابك معطل حالياً، يرجى التواصل مع الإدارة.',
                'code'    => 'account_disabled',
            ], 403);
        }

        return $next($request);
    }
}
