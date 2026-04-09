<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Http\Resources\CouponResource;
use App\Traits\VuetifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    use VuetifyTrait;

    // ─── Admin: List Coupons ──────────────────────────────────────
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        $coupons = $this->scopeDataTable(
            $query, $request,
            searchableColumns: ['code'],
            allowedSortColumns: ['code', 'value', 'used_count', 'expires_at', 'created_at']
        );

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

    // ─── Admin: Export Coupon Usages to Excel ─────────────────────
    public function exportExcel(Coupon $coupon)
    {
        $usages = CouponUsage::where('coupon_id', $coupon->id)
            ->with('user:id,name,phone')
            ->latest()
            ->get();

        $discountLabel = $coupon->type === 'percentage'
            ? $coupon->value . '%'
            : number_format($coupon->value, 0) . ' د.ع';

        $exportedAt = now()->format('Y-m-d H:i');
        $fileName   = 'coupon-' . $coupon->code . '-usages.xls';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // ── Styles ───────────────────────────────────────────────
        $xml .= '<Styles>
            <Style ss:ID="title">
                <Font ss:Bold="1" ss:Size="14" ss:Color="#1565C0"/>
                <Alignment ss:Horizontal="Center"/>
                <Interior ss:Color="#E3F2FD" ss:Pattern="Solid"/>
            </Style>
            <Style ss:ID="meta">
                <Font ss:Bold="1" ss:Size="11" ss:Color="#424242"/>
                <Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>
            </Style>
            <Style ss:ID="header">
                <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/>
                <Interior ss:Color="#1976D2" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#0D47A1"/>
                </Borders>
            </Style>
            <Style ss:ID="rowEven">
                <Interior ss:Color="#FAFAFA" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
                </Borders>
            </Style>
            <Style ss:ID="rowOdd">
                <Interior ss:Color="#E8F4FD" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBDEFB"/>
                </Borders>
            </Style>
            <Style ss:ID="amountCell">
                <Font ss:Bold="1" ss:Color="#2E7D32"/>
                <Alignment ss:Horizontal="Center"/>
            </Style>
            <Style ss:ID="footer">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#0D47A1" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center"/>
            </Style>
        </Styles>' . "\n";

        // ── Worksheet ────────────────────────────────────────────
        $xml .= '<Worksheet ss:Name="سجل الاستخدام">' . "\n";
        $xml .= '<Table ss:DefaultColumnWidth="120">' . "\n";

        // Column widths
        $xml .= '<Column ss:Width="50"/>';   // #
        $xml .= '<Column ss:Width="150"/>';  // Name
        $xml .= '<Column ss:Width="130"/>';  // Phone
        $xml .= '<Column ss:Width="130"/>';  // Discount
        $xml .= '<Column ss:Width="160"/>';  // Date

        // Title row
        $xml .= '<Row ss:Height="30"><Cell ss:MergeAcross="4" ss:StyleID="title">
            <Data ss:Type="String">سجل استخدام كود الخصم — ' . htmlspecialchars($coupon->code) . '</Data>
        </Cell></Row>' . "\n";

        // Meta rows
        $xml .= '<Row ss:Height="22"><Cell ss:StyleID="meta"><Data ss:Type="String">نوع الخصم</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String">' . ($coupon->type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت') . '</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String">قيمة الخصم</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String">' . htmlspecialchars($discountLabel) . '</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String"></Data></Cell>
        </Row>' . "\n";

        $xml .= '<Row ss:Height="22"><Cell ss:StyleID="meta"><Data ss:Type="String">إجمالي الاستخدامات</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="Number">' . $usages->count() . '</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String">تاريخ التصدير</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String">' . $exportedAt . '</Data></Cell>
            <Cell ss:StyleID="meta"><Data ss:Type="String"></Data></Cell>
        </Row>' . "\n";

        // Empty spacer row
        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\n";

        // Header row
        $xml .= '<Row ss:Height="26">
            <Cell ss:StyleID="header"><Data ss:Type="String">#</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">اسم المستخدم</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">رقم الهاتف</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">مقدار الخصم</Data></Cell>
            <Cell ss:StyleID="header"><Data ss:Type="String">تاريخ الاستخدام</Data></Cell>
        </Row>' . "\n";

        // Data rows
        foreach ($usages as $i => $u) {
            $style  = $i % 2 === 0 ? 'rowEven' : 'rowOdd';
            $name   = htmlspecialchars($u->user->name  ?? 'غير معروف');
            $phone  = htmlspecialchars($u->user->phone ?? '-');
            $amount = number_format((float) $u->discount_amount, 0) . ' د.ع';
            $date   = $u->created_at?->format('Y-m-d H:i') ?? '-';

            $xml .= "<Row ss:Height=\"22\">
                <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"Number\">" . ($i + 1) . "</Data></Cell>
                <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">{$name}</Data></Cell>
                <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">{$phone}</Data></Cell>
                <Cell ss:StyleID=\"amountCell\"><Data ss:Type=\"String\">{$amount}</Data></Cell>
                <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">{$date}</Data></Cell>
            </Row>\n";
        }

        // Total footer
        $totalDiscount = $usages->sum('discount_amount');
        $xml .= '<Row ss:Height="24">
            <Cell ss:MergeAcross="2" ss:StyleID="footer"><Data ss:Type="String">إجمالي الخصومات الممنوحة</Data></Cell>
            <Cell ss:StyleID="footer"><Data ss:Type="String">' . number_format($totalDiscount, 0) . ' د.ع</Data></Cell>
            <Cell ss:StyleID="footer"><Data ss:Type="String"></Data></Cell>
        </Row>' . "\n";

        $xml .= '</Table></Worksheet></Workbook>';

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'no-cache, no-store',
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
            'order_id'        => 'nullable|exists:orders,id',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::findOrFail($request->coupon_id);
        $user   = $request->user();

        if (!$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'الكود لم يعد صالحاً.'], 422);
        }

        // ── تحقق: استخدم مرة واحدة فقط لكل زبون ────────────
        $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyUsed) {
            return response()->json(['success' => false, 'message' => 'لقد استخدمت هذا الكود مسبقاً.'], 422);
        }

        DB::transaction(function () use ($coupon, $user, $request) {
            // ── تسجيل الاستخدام ───────────────────────────────
            CouponUsage::create([
                'coupon_id'       => $coupon->id,
                'user_id'         => $user->id,
                'discount_amount' => $request->discount_amount,
            ]);
            $coupon->increment('used_count');

            // ── ربط الكوبون بالطلب إذا أُرسل order_id ────────
            if ($request->order_id) {
                \App\Models\Order::where('id', $request->order_id)
                    ->where('user_id', $user->id)
                    ->update(['coupon_id' => $request->coupon_id]);
            }
        });

        return response()->json(['success' => true, 'message' => 'تم تطبيق كود الخصم بنجاح.']);
    }
}
