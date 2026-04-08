<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    // All available page permissions
    const ALL_PERMISSIONS = [
        'dashboard', 'orders', 'products', 'discounts', 'categories',
        'brands', 'customers', 'notifications', 'coupons', 'banners',
        'districts', 'store-settings', 'settings', 'firebase-settings',
    ];

    public function index(Request $request)
    {
        $admins = Admin::orderBy('id')
            ->select(['id', 'name', 'email', 'is_active', 'is_super_admin', 'permissions', 'created_at'])
            ->paginate($request->get('per_page', 20));

        return response()->json($admins);
    }

    /**
     * Returns current logged-in admin info + permissions
     */
    public function me(Request $request)
    {
        $admin = $request->user();
        return response()->json([
            'id'             => $admin->id,
            'name'           => $admin->name,
            'email'          => $admin->email,
            'is_super_admin' => $admin->is_super_admin,
            'permissions'    => $admin->is_super_admin ? self::ALL_PERMISSIONS : ($admin->permissions ?? []),
        ]);
    }

    public function store(Request $request)
    {
        // Only super admins can create admins
        if (!$request->user()->is_super_admin) {
            return response()->json(['message' => 'ليس لديك صلاحية إضافة مشرفين.'], 403);
        }

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'email'          => 'required|email|unique:admins,email',
            'password'       => ['required', Password::min(8)],
            'is_super_admin' => 'boolean',
            'permissions'    => 'array',
            'permissions.*'  => 'string|in:' . implode(',', self::ALL_PERMISSIONS),
        ]);

        $admin = Admin::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => $data['password'],
            'is_active'      => true,
            'is_super_admin' => $data['is_super_admin'] ?? false,
            'permissions'    => $data['is_super_admin'] ?? false ? [] : ($data['permissions'] ?? []),
        ]);

        return response()->json([
            'message' => 'تم إنشاء المشرف بنجاح.',
            'data'    => $this->formatAdmin($admin),
        ], 201);
    }

    public function update(Request $request, Admin $admin)
    {
        if (!$request->user()->is_super_admin) {
            return response()->json(['message' => 'ليس لديك صلاحية تعديل المشرفين.'], 403);
        }

        $data = $request->validate([
            'name'           => 'sometimes|string|max:100',
            'email'          => 'sometimes|email|unique:admins,email,' . $admin->id,
            'password'       => ['nullable', Password::min(8)],
            'is_super_admin' => 'boolean',
            'permissions'    => 'array',
            'permissions.*'  => 'string|in:' . implode(',', self::ALL_PERMISSIONS),
        ]);

        $updateData = array_filter([
            'name'           => $data['name']           ?? null,
            'email'          => $data['email']          ?? null,
            'is_super_admin' => $data['is_super_admin'] ?? null,
            'permissions'    => isset($data['is_super_admin']) && $data['is_super_admin']
                                    ? []
                                    : ($data['permissions'] ?? null),
        ], fn($v) => $v !== null);

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $admin->update($updateData);

        return response()->json([
            'message' => 'تم تحديث المشرف بنجاح.',
            'data'    => $this->formatAdmin($admin->fresh()),
        ]);
    }

    public function destroy(Request $request, Admin $admin)
    {
        if (!$request->user()->is_super_admin) {
            return response()->json(['message' => 'ليس لديك صلاحية حذف المشرفين.'], 403);
        }

        if ($request->user()->id === $admin->id) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك الخاص.'], 422);
        }

        $admin->tokens()->delete();
        $admin->delete();

        return response()->json(['message' => 'تم حذف المشرف بنجاح.']);
    }

    public function toggleActive(Request $request, Admin $admin)
    {
        if (!$request->user()->is_super_admin) {
            return response()->json(['message' => 'ليس لديك صلاحية تعديل المشرفين.'], 403);
        }

        if ($request->user()->id === $admin->id) {
            return response()->json(['message' => 'لا يمكنك تعطيل حسابك الخاص.'], 422);
        }

        $admin->update(['is_active' => !$admin->is_active]);

        return response()->json([
            'message' => $admin->is_active ? 'تم تفعيل المشرف.' : 'تم تعطيل المشرف.',
            'data'    => $this->formatAdmin($admin),
        ]);
    }

    private function formatAdmin(Admin $admin): array
    {
        return [
            'id'             => $admin->id,
            'name'           => $admin->name,
            'email'          => $admin->email,
            'is_active'      => $admin->is_active,
            'is_super_admin' => $admin->is_super_admin,
            'permissions'    => $admin->permissions ?? [],
            'created_at'     => $admin->created_at?->toDateTimeString(),
        ];
    }
}
