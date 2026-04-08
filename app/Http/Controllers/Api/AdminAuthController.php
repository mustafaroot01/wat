<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !\Hash::check($request->password, $admin->password)) {
            return response()->json(['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'], 401);
        }

        if (!$admin->is_active) {
            return response()->json(['success' => false, 'message' => 'حساب الإدارة هذا معطل.'], 403);
        }

        $admin->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'admin'   => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
            'token' => $admin->createToken('admin-dashboard')->plainTextToken,
        ]);
    }
}
