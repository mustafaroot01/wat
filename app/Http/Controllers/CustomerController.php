<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerController extends Controller
{
    /**
     * قائمة العملاء (مع العملاء المحذوفين لعرضهم بشكل مختلف)
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = User::withTrashed()
            ->with(['district', 'area'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('deleted_at')->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->whereNull('deleted_at')->where('is_active', false);
            } elseif ($request->status === 'deleted') {
                $query->onlyTrashed();
            }
        }

        $customers = $query->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * تعديل بيانات العميل
     */
    public function update(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $data = $request->validate([
            'first_name'  => 'sometimes|required|string|max:100',
            'last_name'   => 'sometimes|required|string|max:100',
            'gender'      => 'sometimes|required|in:male,female',
            'birth_date'  => 'sometimes|required|date',
            'district_id' => 'sometimes|nullable|exists:districts,id',
            'area_id'     => 'sometimes|nullable|exists:areas,id',
            'phone'       => "sometimes|required|string|unique:users,phone,{$id}",
        ]);

        $user->update($data);
        $user->load(['district', 'area']);

        return new CustomerResource($user);
    }

    /**
     * تغيير كلمة مرور العميل من قبل الإدارة
     */
    public function updatePassword(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user->update(['password' => Hash::make($request->password)]);
        // إلغاء جلساته القديمة
        $user->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'تم تغيير كلمة المرور وتسجيل الخروج من جميع أجهزته.']);
    }

    /**
     * تنشيط / إيقاف تنشيط حساب العميل
     */
    public function toggleActive(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $user->update(['is_active' => !$user->is_active]);

        // إذا تم التعطيل، اطرده فوراً من جميع الأجهزة
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        $user->load(['district', 'area']);
        return new CustomerResource($user);
    }

    /**
     * استعادة حساب محذوف (Restore)
     */
    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->update(['is_active' => true]);
        $user->load(['district', 'area']);

        return new CustomerResource($user);
    }
}
