# Auth API — التطبيق

> Base URL: `https://amwaj-app.com/api`  
> Content-Type: `application/json`  
> جميع الردود تحتوي على حقل `success: true/false`

---

## 🔐 تسجيل الدخول

### `POST /auth/login`

لا يحتاج توكن.

**Request Body:**
```json
{
  "phone":    "07801234567",
  "password": "secret123"
}
```

> رقم الهاتف يُقبل بأي صيغة: `07xxxxxxxx` أو `9647xxxxxxxx` أو `+9647xxxxxxxx`

**✅ نجاح — 200:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id":               1,
    "first_name":       "أحمد",
    "last_name":        "علي",
    "full_name":        "أحمد علي",
    "phone":            "+9647801234567",
    "gender":           "male",
    "birth_date":       "1995-06-15",
    "district_id":      3,
    "area_id":          12,
    "nearest_landmark": "قرب المدرسة",
    "district": { "id": 3,  "name": "الكرخ" },
    "area":     { "id": 12, "name": "العامرية" },
    "orders_count":    7,
    "is_active":       true,
    "is_self_deleted": false,
    "created_at":      "2024-01-10 14:23:00"
  }
}
```

**❌ أخطاء:**

| HTTP | `success` | السبب |
|------|-----------|-------|
| 401 | false | رقم الهاتف أو كلمة المرور غير صحيحة |
| 403 | false | الحساب محذوف (`code: account_deleted`) |
| 403 | false | الحساب معطل (`code: account_disabled`) |
| 422 | false | بيانات مفقودة أو غير صالحة |

```json
{ "success": false, "message": "رقم الهاتف أو كلمة المرور غير صحيحة." }
```

```json
{ "success": false, "message": "حسابك معطل حالياً من قبل الإدارة.", "code": "account_disabled" }
```

---

## 📝 التسجيل (خطوتان)

### الخطوة 1 — `POST /auth/register/send-otp`

إرسال بيانات التسجيل كاملة + إرسال رمز OTP على الواتساب.

**Request Body:**
```json
{
  "first_name":       "أحمد",
  "last_name":        "علي",
  "gender":           "male",
  "birth_date":       "1995-06-15",
  "district_id":      3,
  "area_id":          12,
  "nearest_landmark": "قرب المدرسة",
  "phone":            "07801234567",
  "password":         "secret123",
  "password_confirmation": "secret123"
}
```

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `first_name` | string | الاسم الأول — مطلوب |
| `last_name` | string | اسم العائلة — مطلوب |
| `gender` | string | `male` أو `female` — مطلوب |
| `birth_date` | date | تاريخ الميلاد (قبل اليوم) — مطلوب |
| `district_id` | integer | رقم القضاء — مطلوب |
| `area_id` | integer | رقم المنطقة (تابعة للقضاء) — مطلوب |
| `nearest_landmark` | string | أقرب معلم — اختياري |
| `phone` | string | رقم الهاتف — مطلوب وغير مستخدم مسبقاً |
| `password` | string | كلمة المرور (6 أحرف كحد أدنى) — مطلوب |
| `password_confirmation` | string | تأكيد كلمة المرور — مطلوب |

**✅ نجاح — 200:**
```json
{
  "success":            true,
  "message":            "تم إرسال رمز التحقق إلى واتساب بنجاح.",
  "registration_token": "abc123xyz...",
  "message_id":         "+9647801234567"
}
```

> ⚠️ **احفظ `registration_token` و `message_id`** — ستحتاجهما في الخطوة 2.

**❌ أخطاء:**

| HTTP | السبب |
|------|-------|
| 400 | خدمة OTP متوقفة مؤقتاً (نفد الرصيد) |
| 422 | رقم الهاتف مستخدم مسبقاً |
| 422 | الحساب مرتبط برقم محذوف (`code: account_deleted`) |
| 422 | بيانات ناقصة أو غير صالحة (`errors`) |

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "phone":    ["هذا الرقم مستخدم مسبقاً."],
    "password": ["كلمة المرور يجب أن تكون 6 أحرف على الأقل."]
  }
}
```

---

### الخطوة 2 — `POST /auth/register/verify`

إرسال الرمز الذي وصل على الواتساب للتحقق وإنشاء الحساب.

**Request Body:**
```json
{
  "registration_token": "abc123xyz...",
  "message_id":         "+9647801234567",
  "otp":                "492817"
}
```

**✅ نجاح — 201:**
```json
{
  "success": true,
  "message": "أهلاً بك! تم إنشاء حسابك بنجاح.",
  "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id":               5,
    "first_name":       "أحمد",
    "last_name":        "علي",
    "full_name":        "أحمد علي",
    "phone":            "+9647801234567",
    "gender":           "male",
    "birth_date":       "1995-06-15",
    "district_id":      3,
    "area_id":          12,
    "nearest_landmark": "قرب المدرسة",
    "district": { "id": 3,  "name": "الكرخ" },
    "area":     { "id": 12, "name": "العامرية" },
    "orders_count":    0,
    "is_active":       true,
    "is_self_deleted": false,
    "created_at":      "2024-01-15 09:10:00"
  }
}
```

**❌ أخطاء:**

| HTTP | السبب |
|------|-------|
| 422 | انتهت صلاحية جلسة التسجيل (10 دقائق) |
| 422 | رمز OTP غير صحيح أو منتهي الصلاحية |
| 422 | الرقم تم تسجيله في نفس اللحظة (race condition) |
| 429 | يتم معالجة طلب تسجيل آخر بنفس الرقم |

```json
{ "success": false, "message": "رمز التحقق الذي أدخلته غير صحيح أو منتهي الصلاحية." }
```

```json
{ "success": false, "message": "جلسة التسجيل انتهت صلاحيتها، يرجى ملء البيانات مجدداً." }
```

---

## 🔑 نسيت كلمة المرور (خطوتان)

### الخطوة 1 — `POST /auth/forgot-password/send-otp`

**Request Body:**
```json
{
  "phone": "07801234567"
}
```

**✅ نجاح — 200:**
```json
{
  "success":    true,
  "message":    "تم إرسال رمز استعادة كلمة المرور بنجاح.",
  "message_id": "+9647801234567"
}
```

> ⚠️ **احفظ `message_id`** — ستحتاجه في الخطوة 2.

**❌ أخطاء:**

| HTTP | السبب |
|------|-------|
| 404 | الرقم غير مسجل |
| 400 | خدمة OTP متوقفة مؤقتاً (نفد الرصيد) |

```json
{ "success": false, "message": "هذا الرقم غير مسجل لدينا." }
```

---

### الخطوة 2 — `POST /auth/forgot-password/verify`

إرسال الرمز + كلمة المرور الجديدة.

**Request Body:**
```json
{
  "phone":                 "07801234567",
  "message_id":            "+9647801234567",
  "otp":                   "381924",
  "password":              "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**✅ نجاح — 200:**

> يُعيد التوكن الجديد ويُلغي جميع جلسات الأجهزة الأخرى تلقائياً.

```json
{
  "success": true,
  "message": "تم تغيير كلمة المرور بنجاح، يمكنك الدخول الآن.",
  "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id":         5,
    "first_name": "أحمد",
    "full_name":  "أحمد علي",
    "phone":      "+9647801234567",
    ...
  }
}
```

**❌ أخطاء:**

| HTTP | السبب |
|------|-------|
| 422 | رمز OTP غير صحيح |
| 404 | الرقم غير مسجل |
| 403 | الحساب محذوف (`code: account_deleted`) |
| 403 | الحساب معطل (`code: account_disabled`) |

```json
{ "success": false, "message": "رمز التحقق غير صحيح." }
```

---

## 🚪 تسجيل الخروج

### `POST /auth/logout`

يحتاج توكن في الـ Header.

**Headers:**
```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**✅ نجاح — 200:**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج."
}
```

> يُلغي التوكن الحالي فقط ويحذف FCM Token من الجهاز.

---

## 📌 ملاحظات مهمة

### استخدام التوكن
بعد تسجيل الدخول أو التسجيل أو استعادة كلمة المرور — أرسل التوكن في كل طلب محمي:
```
Authorization: Bearer {token}
```

### Rate Limiting (الحماية من الإساءة)
| المسار | الحد الأقصى |
|--------|-------------|
| `/auth/login` | محدود لمنع brute force |
| `/auth/*/send-otp` | محدود لمنع إساءة إرسال OTP |
| `/auth/*/verify` | محدود لمنع تخمين الرمز |

### رموز الأخطاء الخاصة (`code`)
| القيمة | المعنى | الإجراء الموصى به |
|--------|--------|-------------------|
| `account_deleted` | الحساب محذوف بطلب المستخدم | اطلب التواصل مع الدعم |
| `account_disabled` | الحساب معطل إدارياً | اطلب التواصل مع الدعم |

### صلاحية OTP
- رمز OTP صالح لمدة **5 دقائق** من لحظة الإرسال.
- جلسة التسجيل (`registration_token`) صالحة لمدة **10 دقائق**.

### تنسيق رقم الهاتف
الـ API يقبل أي من الصيغ التالية ويحوّلها تلقائياً:
- `07801234567`
- `7801234567`
- `9647801234567`
- `+9647801234567`
