# تقرير المشروع الشامل
**تاريخ التقرير:** 8 أبريل 2026  
**المشروع:** لوحة تحكم + API تطبيق موبايل (E-Commerce / Delivery – السوق العراقي)

---

## 1. نظرة عامة على المشروع

المشروع هو منظومة متكاملة تتكون من جزئين رئيسيين:

| الجزء | التقنية | الوصف |
|---|---|---|
| **الباك-اند (API)** | Laravel 12 + Sanctum | يخدم لوحة التحكم وتطبيق الموبايل |
| **الفرونت-اند (Dashboard)** | Vue.js + Vuetify (Materio Template) | لوحة تحكم الإدارة |
| **قاعدة البيانات** | SQLite (للتطوير) | يمكن التحويل لـ MySQL في الإنتاج |
| **الإشعارات** | Firebase FCM (kreait/laravel-firebase) | إشعارات Push للمستخدمين |
| **OTP / WhatsApp** | arqam.tech API | التحقق برقم الهاتف عند التسجيل |

---

## 2. هيكل قاعدة البيانات (Database Schema)

```
users
  ├── id, name, email (nullable), password
  ├── first_name, last_name, phone (unique)
  ├── gender, birth_date
  ├── district_id (FK → districts), area_id (FK → areas)
  ├── is_active (boolean), is_admin (boolean)
  └── deleted_at (soft delete)

categories
  ├── id, name, slug (unique), image
  ├── is_active, sort_order
  └── timestamps

banners
  ├── id, type (none|link|category|product)
  ├── url, category_id (*), product_id (*)
  ├── duration_type, duration_value
  ├── image, is_active, sort_order
  └── timestamps
  (*) بدون Foreign Key Constraints ← مشكلة مُوثقة

products
  ├── id, name, description, price
  ├── category_id (FK), brand_id (FK)
  ├── image, is_active, sort_order
  └── timestamps

brands
  ├── id, name, slug, image
  ├── is_active, sort_order
  └── timestamps

districts
  ├── id, name, is_active
  └── timestamps

areas
  ├── id, name, district_id (FK), is_active
  └── timestamps

app_notifications
  ├── id, title, message, image
  ├── delivery_status (pending|sent|failed), failure_reason
  ├── sent_at
  └── timestamps

firebase_settings
  ├── id, api_key, auth_domain, project_id
  ├── storage_bucket, messaging_sender_id, app_id, measurement_id
  ├── default_topic
  └── timestamps

settings (key-value store)
  ├── id, key, value
  └── timestamps
```

---

## 3. خارطة الـ API الكاملة

### 3.1 — Admin API  `/api/admin/...`

| Method | Endpoint | Controller | الوصف |
|---|---|---|---|
| POST | `/admin/login` | `AdminAuthController@login` | تسجيل دخول المدير (Email + Password) |
| GET | `/admin/categories` | `CategoryController@index` | قائمة الأقسام (Paginated) |
| POST | `/admin/categories` | `CategoryController@store` | إضافة قسم جديد |
| GET | `/admin/categories/{id}` | `CategoryController@show` | عرض قسم |
| PUT | `/admin/categories/{id}` | `CategoryController@update` | تعديل قسم |
| DELETE | `/admin/categories/{id}` | `CategoryController@destroy` | حذف قسم |
| PATCH | `/admin/categories/{id}/toggle` | `CategoryController@toggleActive` | تفعيل/تعطيل |
| GET/POST/... | `/admin/banners` | `BannerController` | إدارة البانرات الإعلانية |
| PATCH | `/admin/banners/{id}/toggle` | `BannerController@toggle` | تفعيل/تعطيل بانر |
| GET/POST/DELETE | `/admin/notifications` | `NotificationController` | إدارة الإشعارات |
| GET | `/admin/firebase-settings` | `FirebaseSettingsController@index` | إعدادات Firebase |
| POST | `/admin/firebase-settings/save` | `FirebaseSettingsController@storeSettings` | حفظ الإعدادات |
| POST | `/admin/firebase-settings/upload` | `FirebaseSettingsController@uploadJson` | رفع ملف JSON |
| POST | `/admin/firebase-settings/test` | `FirebaseSettingsController@sendTest` | اختبار الإشعارات |
| GET/POST/... | `/admin/districts` | `DistrictController` | إدارة المحافظات |
| PATCH | `/admin/districts/{id}/toggle` | `DistrictController@toggleActive` | تفعيل/تعطيل |
| GET/POST/... | `/admin/areas` | `AreaController` | إدارة المناطق |
| PATCH | `/admin/areas/{id}/toggle` | `AreaController@toggleActive` | تفعيل/تعطيل |
| GET/POST/... | `/admin/brands` | `BrandController` | إدارة العلامات التجارية |
| PATCH | `/admin/brands/{id}/toggle` | `BrandController@toggleActive` | تفعيل/تعطيل |
| GET/POST/... | `/admin/products` | `ProductController` | إدارة المنتجات |
| PATCH | `/admin/products/{id}/toggle` | `ProductController@toggleActive` | تفعيل/تعطيل |
| GET | `/admin/customers` | `CustomerController@index` | قائمة العملاء |
| PUT | `/admin/customers/{id}` | `CustomerController@update` | تعديل بيانات عميل |
| PATCH | `/admin/customers/{id}/toggle` | `CustomerController@toggleActive` | تفعيل/تعطيل حساب |
| PUT | `/admin/customers/{id}/password` | `CustomerController@updatePassword` | تغيير كلمة المرور |
| POST | `/admin/customers/{id}/restore` | `CustomerController@restore` | ⚠️ استعادة حساب (غير مكتملة) |
| GET | `/admin/settings` | `SettingController@index` | الإعدادات العامة |
| POST | `/admin/settings` | `SettingController@store` | حفظ الإعدادات |

**حماية:** جميع مسارات الـ Admin (ماعدا `/login`) محمية بـ `auth:sanctum` + `admin.role`.

---

### 3.2 — Mobile App API  `/api/app/...`

| Method | Endpoint | Controller | الوصف | Auth |
|---|---|---|---|---|
| GET | `/app/categories` | `Api\CategoryController@index` | الأقسام النشطة | Public |
| GET | `/app/notifications` | `NotificationController@index` | الإشعارات | Public |
| GET | `/app/notifications/{id}` | (Closure) | تفاصيل إشعار | Public |
| GET | `/app/brands` | `BrandController@index` | العلامات التجارية | Public |
| GET | `/app/brands/{id}/products` | `BrandController@products` | منتجات علامة تجارية | Public |
| GET | `/app/products` | `ProductController@index` | المنتجات | Public |
| POST | `/app/auth/login` | `Api\AuthController@login` | تسجيل الدخول | Public |
| POST | `/app/auth/register/send-otp` | `Api\AuthController@registerSendOtp` | إرسال OTP للتسجيل | Public |
| POST | `/app/auth/register/verify` | `Api\AuthController@registerVerify` | التحقق وإنشاء الحساب | Public |
| POST | `/app/auth/forgot-password/send-otp` | `Api\AuthController@forgotPasswordSendOtp` | OTP لاسترداد الباسورد | Public |
| POST | `/app/auth/forgot-password/verify` | `Api\AuthController@forgotPasswordVerifyAndReset` | تأكيد وتغيير الباسورد | Public |
| POST | `/app/auth/logout` | `Api\AuthController@logout` | تسجيل الخروج | 🔒 Auth |
| GET | `/app/profile` | `Api\ProfileController@show` | بيانات الملف الشخصي | 🔒 Auth |
| PUT | `/app/profile` | `Api\ProfileController@update` | تعديل البيانات الشخصية | 🔒 Auth |
| PUT | `/app/profile/password` | `Api\ProfileController@updatePassword` | تغيير كلمة المرور | 🔒 Auth |
| DELETE | `/app/profile` | `Api\ProfileController@deleteAccount` | حذف الحساب (Soft) | 🔒 Auth |

---

## 4. المشاكل المكتشفة في المشروع 🔴🟡🟢

---

### 🔴 مشكلة 1 — `restore()` موجودة في المسار لكن غير مكتملة في الـ Controller

**الملف:** `app/Http/Controllers/CustomerController.php` — السطر 108

**الوصف:**  
المسار `/api/admin/customers/{id}/restore` معرَّف في `routes/api.php` ويشير لـ `CustomerController@restore`، لكن الدالة في الـ Controller موجودة فقط كتعليق بدون كود!

```php
// استعادة حساب محذوف (Restore)
}
```

**النتيجة:** أي طلب لهذا المسار سيرجع `500 Internal Server Error` أو يرمي `BadMethodCallException`.

**الحل:**
```php
public function restore(int $id)
{
    $user = User::withTrashed()->findOrFail($id);

    if (!$user->trashed()) {
        return response()->json(['message' => 'الحساب غير محذوف.'], 422);
    }

    $user->restore();
    $user->load(['district', 'area']);

    return new CustomerResource($user);
}
```

---

### 🔴 مشكلة 2 — `User` Model لا يستخدم `SoftDeletes`

**الملف:** `app/Models/User.php`

**الوصف:**  
- `ProfileController@deleteAccount` يصف العملية بأنها "Soft Delete" وينادي `$user->delete()`.
- `CustomerResource` يستخدم `$this->deleted_at` و `$this->is_deleted`.
- `CustomerController` يوجد فيه مسار `restore`.

لكن الـ `User` model لا يحتوي على:
```php
use Illuminate\Database\Eloquent\SoftDeletes;
// و
use SoftDeletes;
```

**النتيجة:**
- `delete()` سيحذف السجل نهائياً من قاعدة البيانات (Hard Delete) وليس وهمياً.
- `deleted_at` دائماً `null` في الـ Response.
- `restore()` ستفشل تماماً.
- الـ `CustomerController@index` لن يُظهر المحذوفين حتى لو طُلب ذلك.

**الحل — في `app/Models/User.php`:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;
    // ...
}
```

وإضافة `deleted_at` للجدول (Migration جديدة):
```php
$table->softDeletes();
```

---

### 🔴 مشكلة 3 — `UserResource` مستورد لكن غير موجود

**الملف:** `app/Http/Controllers/Api/AuthController.php` — السطر 8

```php
use App\Http\Resources\UserResource; // هذا الكلاس غير موجود!
```

لا يوجد ملف `app/Http/Resources/UserResource.php` في المشروع. السطر موجود لكن الكلاس غير مُستخدم حالياً في نفس الملف (يستخدم `CustomerResource` بدلاً منه). لكن هذا يشير إلى بقايا كود (Dead Code) ويسبب مشكلة إذا حُوِّل أي استخدام مستقبلاً لـ `UserResource`.

**الحل:** حذف السطر الزائد:
```php
// احذف هذا السطر:
use App\Http\Resources\UserResource;
```

---

### 🔴 مشكلة 4 — `is_admin` غير موجود في `$casts` في User Model

**الملف:** `app/Models/User.php` — السطر 34-42

الـ `is_active` مُعرَّف في الـ `casts` لكن `is_admin` غير مُعرَّف رغم أنه `boolean` في قاعدة البيانات.

**النتيجة:** قد يرجع `is_admin` كـ `"1"` أو `"0"` (string) بدلاً من `true/false` في بعض الحالات.

**الحل:**
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'birth_date'        => 'date',
        'is_active'         => 'boolean',
        'is_admin'          => 'boolean', // ← أضف هذا السطر
    ];
}
```

---

### 🔴 مشكلة 5 — `banners` جدول بدون Foreign Key Constraints

**الملف:** `database/migrations/2026_04_06_215703_create_banners_table.php` — السطر 20-21

```php
$table->unsignedBigInteger('category_id')->nullable(); // بدون constrained()
$table->unsignedBigInteger('product_id')->nullable();  // بدون constrained()
```

**النتيجة:** لو حذفت قسم (Category) أو منتج (Product) مرتبط ببانر، يبقى الـ `category_id` أو `product_id` في جدول البانرات يشير لسجل غير موجود → Broken Reference.

**الحل:** إنشاء Migration جديدة لإضافة الـ Constraints:
```php
Schema::table('banners', function (Blueprint $table) {
    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
    $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
});
```

---

### 🟡 مشكلة 6 — `AdminAuthController` يُرجع الـ User كاملاً بدون Resource

**الملف:** `app/Http/Controllers/Api/AdminAuthController.php` — السطر 39

```php
'user' => $user, // يُرجع المودل الخام!
```

**النتيجة:** يُرجع جميع حقول الـ User بما فيها `remember_token` وأي حقول حساسة أخرى. على الأقل يجب استخدام `$user->only([...])` أو Resource محدد.

**الحل:**
```php
'user' => [
    'id'         => $user->id,
    'email'      => $user->email,
    'name'       => $user->name,
    'is_admin'   => $user->is_admin,
    'created_at' => $user->created_at,
],
```

---

### 🟡 مشكلة 7 — `BrandController` لا يتحقق من تكرار الـ Slug

**الملف:** `app/Http/Controllers/BrandController.php` — السطر 38

```php
$data['slug'] = Str::slug($data['name']); // بدون فحص التكرار!
```

لكن `CategoryController` يتحقق من التكرار بـ `while` loop. إذا سجلت علامتين بنفس الاسم (مثلاً "سامسونج" مرتين) ستحصل على خطأ `Unique Constraint Violation`.

**الحل:** نفس منطق `CategoryController`:
```php
$baseSlug = Str::slug($data['name']);
$slug = $baseSlug;
$counter = 1;
while (Brand::where('slug', $slug)->exists()) {
    $slug = $baseSlug . '-' . $counter++;
}
$data['slug'] = $slug;
```

---

### 🟡 مشكلة 8 — `Api/CategoryController` لا يستخدم الـ Cache

**الملف:** `app/Http/Controllers/Api/CategoryController.php`

الـ `Category` Model عنده `booted()` يمسح الكاش `api_active_categories` عند كل تغيير، لكن الـ Controller لا يستخدم هذا الكاش أبداً!

**النتيجة:** كل طلب موبايل لـ `/api/app/categories` يذهب مباشرة لقاعدة البيانات بدون استفادة من الكاش.

**الحل:**
```php
public function index()
{
    $categories = Cache::rememberForever('api_active_categories', function () {
        return Category::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    });

    return response()->json([
        'success' => true,
        'data'    => CategoryResource::collection($categories)
    ]);
}
```

---

### 🟡 مشكلة 9 — `CheckAccountActive` Middleware يفتقر للـ Null-Safe Operator

**الملف:** `app/Http/Middleware/CheckAccountActive.php` — السطر 18

```php
$request->user()->currentAccessToken()->delete(); // بدون ?->
```

لكن `AuthController@logout` يستخدم بشكل صحيح:
```php
$request->user()->currentAccessToken()?->delete(); // مع ?->
```

**النتيجة:** في حالة نادرة لو لم يكن للمستخدم Token نشط (مثلاً Token مسحه admin)، ستحصل على `Call to a member function delete() on null`.

**الحل:**
```php
$request->user()->currentAccessToken()?->delete();
```

---

### 🟡 مشكلة 10 — لا يوجد مسار عام للبانرات في App API

**الملف:** `routes/api.php`

تطبيق الموبايل لا يملك مسار `GET /api/app/banners` لجلب البانرات الإعلانية! البانرات مهمة جداً لأي تطبيق تجاري وعادةً تُعرض في الصفحة الرئيسية.

**الحل:** إضافة المسار في مجموعة `/app`:
```php
// Banners Public API
Route::get('banners', [\App\Http\Controllers\BannerController::class, 'indexPublic']);
```

وإضافة دالة `indexPublic` في `BannerController` لإرجاع البانرات النشطة فقط.

---

### 🟡 مشكلة 11 — `CustomerController@index` يُظهر المدراء مع العملاء

**الملف:** `app/Http/Controllers/CustomerController.php` — السطر 20-22

```php
$query = User::query()
    ->with(['district', 'area'])
    ->latest('id');
```

الـ Query لا يُفلتر `is_admin = false`، مما يعني أن حسابات المدراء ستظهر في قائمة العملاء في لوحة التحكم.

**الحل:** إضافة:
```php
$query = User::query()
    ->where('is_admin', false)
    ->with(['district', 'area'])
    ->latest('id');
```

---

### 🟢 مشكلة 12 — `NotificationController` مسار الـ image URL غير موحد

**الملف:** `app/Http/Controllers/NotificationController.php` — السطر 41

```php
$imageUrl = asset('media/' . ltrim($path, '/'));
```

لكن باقي الـ Resources تستخدم نفس النمط. يجب التأكد من أن `media/` هو symlink صحيح يشير لـ `storage/app/public`. يُنفَّذ بأمر `php artisan storage:link`.

---

### 🟢 مشكلة 13 — `BannerResource` لا يتضمن created_at ولا العلاقات

**الملف:** `app/Http/Resources/BannerResource.php`

الـ Resource لا يُرجع:
- `created_at` (مفيد للفرونت-اند لعرض تاريخ الإضافة)
- `category` (اسم القسم المرتبط)
- `product` (اسم المنتج المرتبط)

**الحل:** تحديث الـ Resource:
```php
'category' => $this->whenLoaded('category', fn() => [
    'id'   => $this->category->id,
    'name' => $this->category->name,
]),
'product' => $this->whenLoaded('product', fn() => [
    'id'   => $this->product->id,
    'name' => $this->product->name,
]),
'created_at' => $this->created_at?->format('Y-m-d'),
```

---

## 5. ملخص المشاكل وأولوياتها

| # | المشكلة | الخطورة | السبب |
|---|---|---|---|
| 1 | `restore()` غير مكتملة | 🔴 عالية | Route يشير لدالة فارغة |
| 2 | `SoftDeletes` غير مُفعَّل في User | 🔴 عالية | الحذف يصير Hard Delete |
| 3 | `UserResource` غير موجود | 🔴 متوسطة | Dead Import |
| 4 | `is_admin` بدون Cast | 🔴 متوسطة | Type mismatch |
| 5 | Banners بدون FK Constraints | 🔴 متوسطة | Orphaned records |
| 6 | Admin يرجع User خام | 🟡 متوسطة | Data exposure |
| 7 | Brand Slug بدون uniqueness | 🟡 متوسطة | DB Error عند التكرار |
| 8 | Cache غير مستخدم في Categories | 🟡 متوسطة | أداء |
| 9 | `currentAccessToken()` بدون `?->` | 🟡 منخفضة | PHP Error نادر |
| 10 | لا يوجد مسار Banners للموبايل | 🟡 منخفضة | Feature مفقودة |
| 11 | المدراء يظهرون في قائمة العملاء | 🟡 منخفضة | منطق خاطئ |
| 12 | مسار الـ Image غير موحد | 🟢 منخفضة | Storage Symlink |
| 13 | BannerResource ناقصة | 🟢 منخفضة | تحسين |

---

## 6. خطة الإصلاح المقترحة (بالترتيب)

### المرحلة الأولى — إصلاحات حرجة (يجب قبل الإنتاج)

1. **تفعيل SoftDeletes في User Model** + إنشاء Migration لـ `deleted_at`
2. **إكمال دالة `restore()`** في `CustomerController`
3. **حذف Import الـ `UserResource`** الزائد
4. **إضافة `is_admin` لـ `$casts`** في User Model
5. **إنشاء Migration** لإضافة FK Constraints للبانرات

### المرحلة الثانية — تحسينات مهمة

6. **تصحيح `AdminAuthController`** ليُرجع Resource محدود
7. **إصلاح Brand Slug uniqueness** في `BrandController`
8. **تصحيح `?->` في `CheckAccountActive`**
9. **إضافة فلتر `is_admin = false`** في `CustomerController`

### المرحلة الثالثة — تحسينات الأداء والميزات

10. **تفعيل الكاش** في `Api/CategoryController`
11. **إضافة مسار `/api/app/banners`** للموبايل
12. **تحديث `BannerResource`** ليشمل العلاقات

---

## 7. الخدمات الخارجية المعتمد عليها

| الخدمة | الاستخدام | المتغير البيئي |
|---|---|---|
| **arqam.tech OTP** | إرسال وتحقق WhatsApp OTP | `WHATSAPP_OTP_API_KEY` (أو من DB Settings) |
| **Firebase FCM** | إشعارات Push للمستخدمين | `storage/app/firebase-auth.json` |

> ⚠️ **تحذير:** لو لم يُهيَّأ `WHATSAPP_OTP_API_KEY` في البيئة أو في إعدادات قاعدة البيانات، فإن التسجيل واسترداد الباسورد سيفشلان كلياً بـ Exception.

---

## 8. نقاط قوة المشروع ✅

- **Throttle Rate Limiting** مُطبَّق على جميع مسارات OTP و Login لمنع الـ Brute Force.
- **Race Condition Protection** عند التسجيل باستخدام `Cache::lock()`.
- **Single Device Policy** — حذف جميع الـ Tokens القديمة عند كل تسجيل دخول.
- **Transaction-based** رفع الصور في جميع Controllers (Banner, Category, Brand, Notification).
- **FormRequests** مُنشأة لجميع عمليات Store/Update (ماعدا Customer).
- **Middleware منفصل** لفحص صلاحية الحساب (`CheckAccountActive`) و صلاحيات الإدارة (`CheckAdminRole`).
- **Phone Normalization** يعالج أرقام الهاتف العراقية بجميع صيغها (+964, 964, 07...).
- **API Resources** لجميع الـ Models لمنع تسريب البيانات الحساسة.

---

*نهاية التقرير — تم إعداده بتحليل شامل لجميع ملفات المشروع*
