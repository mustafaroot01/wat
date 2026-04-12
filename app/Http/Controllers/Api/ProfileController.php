<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * عرض معلومات الملف الشخصي
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => new CustomerResource($request->user()->load(['district', 'area'])),
        ]);
    }

    /**
     * تحديث البيانات الشخصية
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $districtId = $request->district_id ?? $user->district_id;

        $data = $request->validate([
            'first_name'  => 'sometimes|required|string|max:100',
            'last_name'   => 'sometimes|required|string|max:100',
            'gender'      => 'sometimes|required|in:male,female',
            'birth_date'  => 'sometimes|required|date|before:today',
            'district_id'      => 'sometimes|required|exists:districts,id',
            'area_id'          => ['sometimes', 'required', Rule::exists('areas', 'id')->where('district_id', $districtId)],
            'nearest_landmark' => 'sometimes|nullable|string|max:255',
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات ملفك الشخصي بنجاح.',
            'user'    => new CustomerResource($user->load(['district', 'area'])),
        ]);
    }

    /**
     * تغيير كلمة المرور
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password', 
            'new_password'     => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }

    /**
     * طلب حذف الحساب — يُعطَّل الحساب ويُسجَّل الطلب للإدارة
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        // تعطيل الحساب وتسجيل طلب الحذف
        $user->update([
            'is_active'       => false,
            'is_self_deleted' => true,
        ]);

        // إلغاء جميع الجلسات على كل الأجهزة
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إيقاف حسابك بنجاح. يمكنك التواصل مع الإدارة لاستعادته.',
        ]);
    }
}
