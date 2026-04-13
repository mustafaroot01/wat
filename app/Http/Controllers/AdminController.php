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
        'credits', 'admins',
    ];

    /** True if requester can manage admins (super admin OR has 'admins' permission) */
    private function canManageAdmins(Admin $requester): bool
    {
        return $requester->is_super_admin || $requester->hasPermission('admins');
    }

    /** Permissions the requester is allowed to assign to others */
    private function assignablePermissions(Admin $requester): array
    {
        if ($requester->is_super_admin) return self::ALL_PERMISSIONS;
        return $requester->permissions ?? [];
    }

    public function index(Request $request)
    {
        if (!$this->canManageAdmins($request->user())) {
            return response()->json(['message' => 'ليس لديك صلاحية عرض المشرفين.'], 403);
        }

        $admins = Admin::orderBy('id')
            ->select(['id', 'name', 'email', 'is_active', 'is_super_admin', 'permissions', 'created_at'])
            ->paginate($request->get('per_page', 50));

        return response()->json($admins);
    }

    /**
     * Returns current logged-in admin info + permissions + what they can assign
     */
    public function me(Request $request)
    {
        $admin = $request->user();
        return response()->json([
            'id'                   => $admin->id,
            'name'                 => $admin->name,
            'email'                => $admin->email,
            'is_super_admin'       => $admin->is_super_admin,
            'permissions'          => $admin->is_super_admin ? self::ALL_PERMISSIONS : ($admin->permissions ?? []),
            'can_manage_admins'    => $this->canManageAdmins($admin),
            'assignable_permissions' => $this->assignablePermissions($admin),
        ]);
    }

    public function store(Request $request)
    {
        $requester = $request->user();
        if (!$this->canManageAdmins($requester)) {
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

        // Non-super-admin owners cannot create super admins
        $makeSuperAdmin = ($data['is_super_admin'] ?? false) && $requester->is_super_admin;

        // Restrict permissions to what the requester can assign
        $allowedPerms = $this->assignablePermissions($requester);
        $permissions  = array_values(array_intersect($data['permissions'] ?? [], $allowedPerms));

        $admin = Admin::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => $data['password'],
            'is_active'      => true,
            'is_super_admin' => $makeSuperAdmin,
            'permissions'    => $makeSuperAdmin ? [] : $permissions,
        ]);

        return response()->json([
            'message' => 'تم إنشاء المشرف بنجاح.',
            'data'    => $this->formatAdmin($admin),
        ], 201);
    }

    public function update(Request $request, Admin $admin)
    {
        $requester = $request->user();
        if (!$this->canManageAdmins($requester)) {
            return response()->json(['message' => 'ليس لديك صلاحية تعديل المشرفين.'], 403);
        }

        // Non-super-admin cannot edit a super admin
        if (!$requester->is_super_admin && $admin->is_super_admin) {
            return response()->json(['message' => 'لا يمكنك تعديل حساب مشرف رئيسي.'], 403);
        }

        $data = $request->validate([
            'name'           => 'sometimes|string|max:100',
            'email'          => 'sometimes|email|unique:admins,email,' . $admin->id,
            'password'       => ['nullable', Password::min(8)],
            'is_super_admin' => 'boolean',
            'permissions'    => 'array',
            'permissions.*'  => 'string|in:' . implode(',', self::ALL_PERMISSIONS),
        ]);

        $makeSuperAdmin = isset($data['is_super_admin']) ? ($data['is_super_admin'] && $requester->is_super_admin) : $admin->is_super_admin;

        $allowedPerms = $this->assignablePermissions($requester);
        $permissions  = isset($data['permissions'])
            ? array_values(array_intersect($data['permissions'], $allowedPerms))
            : $admin->permissions;

        $updateData = [];
        if (isset($data['name']))  $updateData['name']  = $data['name'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        $updateData['is_super_admin'] = $makeSuperAdmin;
        $updateData['permissions']    = $makeSuperAdmin ? [] : $permissions;

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
        $requester = $request->user();
        if (!$this->canManageAdmins($requester)) {
            return response()->json(['message' => 'ليس لديك صلاحية حذف المشرفين.'], 403);
        }

        if ($requester->id === $admin->id) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك الخاص.'], 422);
        }

        // Non-super-admin cannot delete a super admin
        if (!$requester->is_super_admin && $admin->is_super_admin) {
            return response()->json(['message' => 'لا يمكنك حذف مشرف رئيسي.'], 403);
        }

        $admin->tokens()->delete();
        $admin->delete();

        return response()->json(['message' => 'تم حذف المشرف بنجاح.']);
    }

    public function toggleActive(Request $request, Admin $admin)
    {
        $requester = $request->user();
        if (!$this->canManageAdmins($requester)) {
            return response()->json(['message' => 'ليس لديك صلاحية تعديل المشرفين.'], 403);
        }

        if ($requester->id === $admin->id) {
            return response()->json(['message' => 'لا يمكنك تعطيل حسابك الخاص.'], 422);
        }

        if (!$requester->is_super_admin && $admin->is_super_admin) {
            return response()->json(['message' => 'لا يمكنك تعطيل مشرف رئيسي.'], 403);
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
