<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'coupon'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage = max(1, min((int) $request->get('per_page', 25), 100));
        $orders = $query->with(['items', 'coupon'])->paginate($perPage);

        return response()->json([
            'data' => $orders->items(),
            'total' => $orders->total(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'coupon'])->findOrFail($id);
        $settings = StoreSetting::allAsArray();

        return response()->json([
            'order' => $order,
            'settings' => $settings,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'           => ['required', 'in:' . implode(',', Order::STATUSES)],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;

        if ($request->status === Order::STATUS_REJECTED) {
            $order->rejection_reason = $request->rejection_reason;
        }

        $order->save();

        return response()->json(['message' => 'تم تحديث حالة الطلب', 'order' => $order]);
    }

    // Public invoice by token (for QR code)
    public function invoiceByToken($token)
    {
        $order = Order::with(['items.product', 'coupon'])
            ->where('invoice_token', $token)
            ->firstOrFail();

        $settings = StoreSetting::allAsArray();

        return response()->json([
            'order'    => $order,
            'settings' => $settings,
        ]);
    }

    // Bulk invoice by IDs (admin only)
    public function bulkInvoice(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        $orders = Order::with(['items.product', 'coupon'])
            ->whereIn('id', array_filter($ids))
            ->orderBy('id', 'desc')
            ->get();

        $settings = StoreSetting::allAsArray();

        return response()->json([
            'orders'   => $orders,
            'settings' => $settings,
        ]);
    }

    // Status update from QR invoice page — requires admin auth (checked in route)
    public function updateStatusByToken(Request $request, $token)
    {
        $request->validate([
            'status'           => ['required', 'in:' . implode(',', Order::STATUSES)],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::where('invoice_token', $token)->firstOrFail();
        $order->status = $request->status;

        if ($request->status === Order::STATUS_REJECTED) {
            $order->rejection_reason = $request->rejection_reason;
        }

        $order->save();

        return response()->json(['message' => 'تم تحديث حالة الطلب', 'order' => $order]);
    }

    // Admin: delete order
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح']);
    }

    // App: list own orders
    public function myOrders(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $orders = Order::with(['items', 'coupon'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data'         => $orders->items(),
            'total'        => $orders->total(),
            'current_page' => $orders->currentPage(),
            'last_page'    => $orders->lastPage(),
        ]);
    }

    // App: single order details (only own order)
    public function myOrderShow(Request $request, $id)
    {
        $order = Order::with(['items.product', 'coupon'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    // App: create order
    public function store(Request $request)
    {
        $request->validate([
            'customer_name'     => ['required', 'string'],
            'customer_phone'    => ['required', 'string'],
            'province'          => ['required', 'string'],
            'district'          => ['required', 'string'],
            'nearest_landmark'  => ['nullable', 'string'],
            'notes'             => ['nullable', 'string'],
            'coupon_id'         => ['nullable', 'exists:coupons,id'],
            'discount_amount'   => ['nullable', 'numeric', 'min:0'],
            'total_amount'      => ['required', 'numeric', 'min:0'],
            'final_amount'      => ['required', 'numeric', 'min:0'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id'=> ['required', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.sku'       => ['nullable', 'string'],
            'items.*.unit_price'=> ['required', 'numeric', 'min:0'],
            'items.*.quantity'  => ['required', 'integer', 'min:1'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
        ]);

        // ─── فحص ساعات الدوام ─────────────────────────────
        if (!StoreSetting::isOpenNow()) {
            return response()->json([
                'success'      => false,
                'store_closed' => true,
                'message'      => 'المتجر مغلق حالياً، يُرجى المحاولة خلال أوقات الدوام.',
            ], 422);
        }

        // ─── فحص أقل مبلغ للطلب ───────────────────────────
        $minimumOrder = (float) StoreSetting::get('minimum_order_amount', 0);
        if ($minimumOrder > 0 && $request->final_amount < $minimumOrder) {
            return response()->json([
                'success' => false,
                'message' => "الحد الأدنى للطلب هو " . number_format($minimumOrder, 0) . " د.ع — طلبك الحالي " . number_format($request->final_amount, 0) . " د.ع",
                'minimum_order_amount' => $minimumOrder,
            ], 422);
        }

        // ─── فحص: كل زبون يستخدم الكوبون مرة واحدة فقط ──
        if ($request->coupon_id) {
            $alreadyUsed = \App\Models\CouponUsage::where('coupon_id', $request->coupon_id)
                ->where('user_id', $request->user()->id)
                ->exists();
            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استخدمت هذا الكود مسبقاً.',
                ], 422);
            }
        }
        // ────────────────────────────────────────────────────

        $order = Order::create([
            'user_id'          => $request->user()->id,
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'province'         => $request->province,
            'district'         => $request->district,
            'nearest_landmark' => $request->nearest_landmark,
            'status'           => Order::STATUS_SENT,
            'total_amount'     => $request->total_amount,
            'discount_amount'  => $request->discount_amount ?? 0,
            'final_amount'     => $request->final_amount,
            'coupon_id'        => $request->coupon_id,
            'notes'            => $request->notes,
        ]);

        foreach ($request->items as $item) {
            $order->items()->create($item);
        }

        // ─── تسجيل استخدام الكوبون (إذا أُرسل في body الطلب) ──
        if ($request->coupon_id) {
            $coupon = \App\Models\Coupon::find($request->coupon_id);
            if ($coupon) {
                $alreadyUsed = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $request->user()->id)
                    ->exists();
                if (!$alreadyUsed) {
                    \App\Models\CouponUsage::create([
                        'coupon_id'       => $coupon->id,
                        'user_id'         => $request->user()->id,
                        'discount_amount' => $request->discount_amount ?? 0,
                    ]);
                    $coupon->increment('used_count');
                }
            }
        }
        // ────────────────────────────────────────────────────

        return response()->json([
            'message' => 'تم إرسال الطلب بنجاح',
            'order'   => $order->load(['items', 'coupon']),
        ], 201);
    }

    // App: cancel own order (only if status = sent)
    public function cancel(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->status !== Order::STATUS_SENT) {
            return response()->json(['message' => 'لا يمكن إلغاء الطلب في هذه المرحلة'], 422);
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return response()->json(['message' => 'تم إلغاء الطلب']);
    }
}
