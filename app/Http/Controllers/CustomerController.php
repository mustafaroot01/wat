<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\CustomerResource;
use App\Traits\VuetifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerController extends Controller
{
    use VuetifyTrait;

    public function index(Request $request)
    {
        $query = User::query()->with(['district', 'area'])->withCount('orders');

        if ($request->filled('status')) {
            match ($request->status) {
                'active'   => $query->where('is_active', true)->where('is_self_deleted', false),
                'inactive' => $query->where('is_active', false)->where('is_self_deleted', false),
                'deleted'  => $query->where('is_self_deleted', true),
                default    => null,
            };
        }

        $customers = $this->scopeDataTable(
            $query, $request,
            searchableColumns: ['first_name', 'last_name', 'phone'],
            allowedSortColumns: ['first_name', 'last_name', 'phone', 'orders_count', 'created_at']
        );

        return CustomerResource::collection($customers)
            ->additional(['has_more' => $customers->hasMorePages()]);
    }

    public function orders(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $query = \App\Models\Order::where('user_id', $user->id)->with(['items', 'coupon']);

        $orders = $this->scopeDataTable(
            $query, $request,
            searchableColumns: ['status', 'notes'],
            allowedSortColumns: ['id', 'status', 'total_amount', 'created_at']
        );

        return response()->json([
            'customer' => new CustomerResource($user),
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
            'has_more' => $orders->hasMorePages(),
        ]);
    }

    /**
     * تعديل بيانات العميل
     */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $districtId = $request->district_id ?? $user->district_id;

        $data = $request->validate([
            'first_name'  => 'sometimes|required|string|max:100',
            'last_name'   => 'sometimes|required|string|max:100',
            'gender'      => 'sometimes|required|in:male,female',
            'birth_date'  => 'sometimes|required|date',
            'district_id' => 'sometimes|nullable|exists:districts,id',
            'area_id'     => ['sometimes', 'nullable', Rule::exists('areas', 'id')->where('district_id', $districtId)],
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
        $user = User::findOrFail($id);

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
        $user = User::findOrFail($id);

        $user->update(['is_active' => !$user->is_active]);

        // إذا تم التعطيل، اطرده فوراً من جميع الأجهزة
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        $user->load(['district', 'area']);
        return new CustomerResource($user);
    }

    /**
     * استعادة حساب self-deleted من قبل الإدارة
     */
    public function restore(int $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_self_deleted' => false,
            'is_active'       => true,
        ]);

        $user->load(['district', 'area']);

        return new CustomerResource($user);
    }

}
