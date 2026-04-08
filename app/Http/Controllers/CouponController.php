<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Http\Resources\CouponResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    // ─── Admin: List Coupons ──────────────────────────────────────
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $coupons = $query->latest()->paginate($request->get('per_page', 15));

        return CouponResource::collection($coupons)
            ->additional(['has_more' => $coupons->hasMorePages()]);
    }

    // ─── Admin: Create Coupon ─────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date|after:now',
            'is_active'        => 'nullable|boolean',
        ]);

        if ($data['type'] === 'percentage' && $data['value'] > 100) {
            return response()->json(['message' => 'نسبة الخصم لا يمكن أن تتجاوز 100%.'], 422);
        }

        $coupon = Coupon::create($data);

        return new CouponResource($coupon);
    }

    // ─── Admin: Show Coupon ───────────────────────────────────────
    public function show(Coupon $coupon)
    {
        return new CouponResource($coupon->loadCount('usages'));
    }

    // ─── Admin: Paginated Usage List for a Coupon ─────────────────
    public function usages(Request $request, Coupon $coupon)
    {
        $perPage = min((int) $request->get('per_page', 50), 200);

        $usages = CouponUsage::where('coupon_id', $coupon->id)
            ->with('user:id,name,phone')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'coupon'   => new CouponResource($coupon),
            'data'     => $usages->map(fn($u) => [
                'id'              => $u->id,
                'user_name'       => $u->user->name  ?? 'غير معروف',
                'user_phone'      => $u->user->phone ?? '-',
                'discount_amount' => (float) $u->discount_amount,
                'used_at'         => $u->created_at?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $usages->currentPage(),
                'last_page'    => $usages->lastPage(),
                'per_page'     => $usages->perPage(),
                'total'        => $usages->total(),
            ],
            'has_more' => $usages->hasMorePages(),
        ]);
    }

    // ─── Admin: Update Coupon ─────────────────────────────────────
    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code'             => 'sometimes|required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'             => 'sometimes|required|in:percentage,fixed',
            'value'            => 'sometimes|required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'nullable|boolean',
        ]);

        if (isset($data['type'], $data['value']) && $data['type'] === 'percentage' && $data['value'] > 100) {
            return response()->json(['message' => 'نسبة الخصم لا يمكن أن تتجاوز 100%.'], 422);
        }

        $coupon->update($data);

        return new CouponResource($coupon);
    }

    // ─── Admin: Delete Coupon ─────────────────────────────────────
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['message' => 'تم حذف كود الخصم بنجاح.']);
    }

    // ─── Admin: Toggle Active ─────────────────────────────────────
    public function toggleActive(Coupon $coupon)
    {
        $coupon->update(['is_active' => !$coupon->is_active]);
        return new CouponResource($coupon);
    }

    // ─── App: Validate Coupon ─────────────────────────────────────
    public function validate(Request $request)
    {
        $request->validate([
            'code'         => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'كود الخصم غير صحيح.'], 404);
        }

        if (!$coupon->isValid()) {
            $msg = 'كود الخصم غير متاح.';
            if ($coupon->expires_at?->isPast()) $msg = 'انتهت صلاحية كود الخصم.';
            elseif ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) $msg = 'تم استنفاد عدد مرات استخدام هذا الكود.';
            elseif (!$coupon->is_active) $msg = 'كود الخصم معطّل حالياً.';
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        if ($coupon->min_order_amount && $request->order_amount < $coupon->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'الحد الأدنى للطلب هو ' . number_format($coupon->min_order_amount, 0) . ' د.ع.',
            ], 422);
        }

        $user = $request->user();
        if ($user) {
            $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->exists();
            if ($alreadyUsed) {
                return response()->json(['success' => false, 'message' => 'لقد استخدمت هذا الكود مسبقاً.'], 422);
            }
        }

        $discountAmount = $coupon->calculateDiscount((float) $request->order_amount);
        $finalAmount    = max(0, (float) $request->order_amount - $discountAmount);

        return response()->json([
            'success'         => true,
            'message'         => 'كود الخصم صحيح!',
            'coupon_id'       => $coupon->id,
            'type'            => $coupon->type,
            'value'           => (float) $coupon->value,
            'discount_amount' => $discountAmount,
            'final_amount'    => $finalAmount,
        ]);
    }

    // ─── App: Apply Coupon (call after order confirmed) ───────────
    public function apply(Request $request)
    {
        $request->validate([
            'coupon_id'       => 'required|exists:coupons,id',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::findOrFail($request->coupon_id);
        $user   = $request->user();

        if (!$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'الكود لم يعد صالحاً.'], 422);
        }

        $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->exists();
        if ($alreadyUsed) {
            return response()->json(['success' => false, 'message' => 'لقد استخدمت هذا الكود مسبقاً.'], 422);
        }

        DB::transaction(function () use ($coupon, $user, $request) {
            CouponUsage::create([
                'coupon_id'       => $coupon->id,
                'user_id'         => $user->id,
                'discount_amount' => $request->discount_amount,
            ]);
            $coupon->increment('used_count');
        });

        return response()->json(['success' => true, 'message' => 'تم تطبيق كود الخصم بنجاح.']);
    }
}
