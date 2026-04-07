<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'حساب الإدارة هذا معطل.'], 403);
        }

        // إغلاق أي جلسات سابقة
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'user' => $user,
            'token' => $user->createToken('admin-dashboard')->plainTextToken
        ]);
    }
}
