<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
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
        
        // البحث عن المستخدم
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة.'], 401);
        }

        // حساب طلب المستخدم حذفه بنفسه
        if ($user->is_self_deleted) {
            return response()->json(['success' => false, 'message' => 'تم حذف هذا الحساب بناءً على طلبك. تواصل مع الإدارة لاستعادته.', 'code' => 'account_deleted'], 403);
        }

        // حساب معطل إدارياً
        if (!$user->is_active) {
            $user->tokens()->delete();
            return response()->json(['success' => false, 'message' => 'حسابك معطل حالياً من قبل الإدارة.', 'code' => 'account_disabled'], 403);
        }

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
        $request->user()->update(['fcm_token' => null]);
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج.']);
    }

    /**
     * الخطوة 1 للتسجيل: إرسال الـ OTP
     */
    public function registerSendOtp(Request $request)
    {
        $request['phone'] = OtpService::normalizePhone($request->phone ?? '');

        // رقم هاتف يخص حساباً طلب حذفه — أبلغه بدلاً من رسالة "مستخدم مسبقاً" الغامضة
        $existingDeleted = User::where('phone', $request->phone)->where('is_self_deleted', true)->exists();
        if ($existingDeleted) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرقم مرتبط بحساب تم حذفه سابقاً. تواصل مع الإدارة لاستعادته.',
                'code'    => 'account_deleted',
            ], 422);
        }

        $data = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'gender'      => 'required|in:male,female',
            'birth_date'  => 'nullable|date|before:today',
            'district_id'      => 'required|exists:districts,id',
            'area_id'          => ['required', Rule::exists('areas', 'id')->where('district_id', $request->district_id)],
            'nearest_landmark' => 'nullable|string|max:255',
            'phone'            => 'required|string|unique:users,phone',
            'password'         => ['required', 'confirmed', Password::min(6)],
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

        $user = User::where('phone', $request->phone)->first();
        
        if (!$user) return response()->json(['success' => false, 'message' => 'هذا الرقم غير مسجل لدينا.'], 404);

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
     * نسيان كلمة المرور (خطوة 2): التحقق من OTP فقط → إرجاع reset_token
     */
    public function forgotPasswordVerifyOtp(Request $request)
    {
        $request->validate([
            'message_id' => 'required|string',
            'otp'        => 'required|string',
        ]);

        $verify = $this->otp->verifyOtp($request->message_id, $request->otp);

        if (!($verify['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.'], 422);
        }

        $normalizedPhone = OtpService::normalizePhone($request->message_id);
        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف غير مسجل لدينا.'], 404);
        }

        // إنشاء reset_token مؤقت صالح 10 دقائق
        $resetToken = Str::random(64);
        Cache::put('pwd_reset_' . $resetToken, $normalizedPhone, now()->addMinutes(10));

        return response()->json([
            'success'     => true,
            'message'     => 'تم التحقق بنجاح.',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * نسيان كلمة المرور (خطوة 3): تغيير الباسورد باستخدام reset_token
     */
    public function forgotPasswordReset(Request $request)
    {
        $request->validate([
            'reset_token' => 'required|string',
            'password'    => ['required', 'confirmed', Password::min(6)],
        ]);

        $phone = Cache::get('pwd_reset_' . $request->reset_token);

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'انتهت صلاحية جلسة إعادة التعيين، يرجى البدء من جديد.'], 422);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف غير مسجل لدينا.'], 404);
        }

        if ($user->is_self_deleted) {
            return response()->json(['success' => false, 'message' => 'تم حذف هذا الحساب، تواصل مع الإدارة.', 'code' => 'account_deleted'], 403);
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'حسابك معطل من قبل الإدارة.', 'code' => 'account_disabled'], 403);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();
        Cache::forget('pwd_reset_' . $request->reset_token);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
            'user'    => new CustomerResource($user->load(['district', 'area'])),
            'token'   => $user->createToken('mobile')->plainTextToken,
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

        // message_id هو الرقم المعيار الذي أُرسل إليه الـ OTP — نستخدمه مباشرة
        $normalizedPhone = OtpService::normalizePhone($request->message_id);
        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف هذا غير مسجل لدينا.'], 404);
        }

        if ($user->is_self_deleted) {
            return response()->json(['success' => false, 'message' => 'تم حذف هذا الحساب بناءً على طلبك. تواصل مع الإدارة لاستعادته.', 'code' => 'account_deleted'], 403);
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'لا يمكن تغيير الباسورد، حسابك معطل من قبل الإدارة.', 'code' => 'account_disabled'], 403);
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
