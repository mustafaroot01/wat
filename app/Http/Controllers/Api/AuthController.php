<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Http\Resources\UserResource;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /**
     * تسجيل الدخول (رقم هاتف + رمز سري)
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        $phone = OtpService::normalizePhone($request->phone);
        
        // البحث عن المستخدم (حتى لو كان محذوفاً للتمييز)
        $user = User::withTrashed()->where('phone', $phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة.'], 401);
        }

        // تمييز الحساب المحذوف (من قبل الزبون)
        if ($user->trashed()) {
            return response()->json([
                'success' => false, 
                'message' => 'لقد قمت بحذف حسابك مسبقاً. يرجى التواصل مع الإدارة لإعادة تفعيله.'
            ], 403);
        }

        // تمييز الحساب المعطل (إدارياً)
        if (!$user->is_active) {
            $user->tokens()->delete();
            return response()->json(['success' => false, 'message' => 'حسابك معطل حالياً من قبل الإدارة.'], 403);
        }

        // طرد من جميع الأجهزة الأخرى (Single device policy)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'user'    => new CustomerResource($user->load(['district', 'area'])),
            'token'   => $user->createToken('mobile')->plainTextToken,
        ]);
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج.']);
    }

    /**
     * الخطوة 1 للتسجيل: إرسال الـ OTP
     */
    public function registerSendOtp(Request $request)
    {
        $request['phone'] = OtpService::normalizePhone($request->phone ?? '');

        $data = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'gender'      => 'required|in:male,female',
            'birth_date'  => 'required|date|before:today',
            'district_id' => 'required|exists:districts,id',
            'area_id'     => 'required|exists:areas,id',
            'phone'       => 'required|string|unique:users,phone',
            'password'    => ['required', 'confirmed', Password::min(6)],
        ]);

        $result = $this->otp->sendOtp($data['phone']);

        if (!($result['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'فشل إرسال الرمز.'], 400);
        }

        // إنشاء جلسة تسجيل آمنة (Token based) لتجنب إعادة إرسال حمولة البيانات
        $registrationToken = Str::random(40);
        $data['message_id'] = $result['messageId'] ?? null;
        
        Cache::put('register_' . $registrationToken, $data, now()->addMinutes(10));

        return response()->json([
            'success'            => true,
            'message'            => 'تم إرسال رمز التحقق إلى واتساب بنجاح.',
            'registration_token' => $registrationToken,
            'message_id'         => $result['messageId'] ?? null,
        ]);
    }

    /**
     * الخطوة 2 للتسجيل: التحقق والإنشاء الفعلي
     */
    public function registerVerify(Request $request)
    {
        $request->validate([
            'registration_token' => 'required|string',
            'otp'                => 'required|string',
            'message_id'         => 'required|string',
        ]);

        $data = Cache::get('register_' . $request->registration_token);

        if (!$data || $data['message_id'] !== $request->message_id) {
            return response()->json(['success' => false, 'message' => 'جلسة التسجيل انتهت صلاحيتها، يرجى ملء البيانات مجدداً.'], 422);
        }

        // فحص الـ OTP من المزود
        $verify = $this->otp->verifyOtp($request->message_id, $request->otp);

        if (!($verify['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'رمز التحقق الذي أدخلته غير صحيح أو منتهي الصلاحية.'], 422);
        }

        // منع حالات السباق (Race Condition) بالأقفال الذكية
        $lock = Cache::lock('register_lock_' . $data['phone'], 10);

        if ($lock->get()) {
            try {
                $data['password'] = Hash::make($data['password']);
                $data['is_active'] = true;
                
                $user = User::create($data); 

                Cache::forget('register_' . $request->registration_token);

            } catch (QueryException $e) {
                if ($e->errorInfo[1] == 1062) { // Duplicate entry
                    return response()->json(['success' => false, 'message' => 'هذا الرقم تم استخدامه للتسجيل قبل لحظات.'], 422);
                }
                throw $e;
            } finally {
                $lock->release();
            }

            return response()->json([
                'success' => true,
                'message' => 'أهلاً بك! تم إنشاء حسابك بنجاح.',
                'user'    => new CustomerResource($user->load(['district', 'area'])),
                'token'   => $user->createToken('mobile')->plainTextToken,
            ], 201);
        }

        return response()->json(['success' => false, 'message' => 'يتم حالياً معالجة طلب تسجيل بالرقم، يرجى المحاولة بعد لحظات.'], 429);
    }

    /**
     * نسيان كلمة المرور: إرسال الـ OTP للرقم المسجل
     */
    public function forgotPasswordSendOtp(Request $request)
    {
        $request['phone'] = OtpService::normalizePhone($request->phone ?? '');
        $request->validate(['phone' => 'required']);

        $user = User::withTrashed()->where('phone', $request->phone)->first();
        
        if (!$user) return response()->json(['success' => false, 'message' => 'هذا الرقم غير مسجل لدينا.'], 404);
        if ($user->trashed()) return response()->json(['success' => false, 'message' => 'هذا الحساب محذوف مسبقاً.'], 403);

        $result = $this->otp->sendOtp($request->phone);

        if (!($result['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'فشل إرسال رمز التحقق للواتساب.'], 400);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'تم إرسال رمز استعادة كلمة المرور بنجاح.',
            'message_id' => $result['messageId'] ?? null,
        ]);
    }

    /**
     * نسيان كلمة المرور: التحقق وتعيين الباسورد والدخول التلقائي
     */
    public function forgotPasswordVerifyAndReset(Request $request)
    {
        $request['phone'] = OtpService::normalizePhone($request->phone ?? '');
        
        $request->validate([
            'phone'      => 'required',
            'message_id' => 'required|string',
            'otp'        => 'required|string',
            'password'   => ['required', 'confirmed', Password::min(6)],
        ]);

        $verify = $this->otp->verifyOtp($request->message_id, $request->otp);

        if (!($verify['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'رمز التحقق غير صحيح.'], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف هذا غير مسجل لدينا.'], 404);
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'لا يمكن تغيير الباسورد، حسابك معطل حالياً.'], 403);
        }

        $user->update(['password' => Hash::make($request->password)]);
        
        // تسجيل خروج من جميع الأجهزة تمهيداً للدخول الجديد
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح، يمكنك الدخول الآن.',
            'user'    => new CustomerResource($user->load(['district', 'area'])),
            'token'   => $user->createToken('mobile')->plainTextToken,
        ]);
    }
}
