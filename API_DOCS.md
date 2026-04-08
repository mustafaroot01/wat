# توثيق API — تطبيق امواج ديالى

**Base URL:** `https://wat.diyala.org/api/app`  
**Content-Type:** `application/json`  
**Accept:** `application/json`

---

## المحتويات

1. [الملاحظات العامة](#1-الملاحظات-العامة)
2. [المصادقة — Auth](#2-المصادقة--auth)
   - [تسجيل الدخول](#21-تسجيل-الدخول)
   - [التسجيل — إرسال OTP](#22-التسجيل--إرسال-otp)
   - [التسجيل — تأكيد OTP وإنشاء الحساب](#23-التسجيل--تأكيد-otp-وإنشاء-الحساب)
   - [نسيت كلمة المرور — إرسال OTP](#24-نسيت-كلمة-المرور--إرسال-otp)
   - [نسيت كلمة المرور — تأكيد وإعادة التعيين](#25-نسيت-كلمة-المرور--تأكيد-وإعادة-التعيين)
   - [تسجيل الخروج](#26-تسجيل-الخروج)
3. [الملف الشخصي — Profile](#3-الملف-الشخصي--profile)
   - [عرض البيانات](#31-عرض-بيانات-المستخدم)
   - [تعديل البيانات](#32-تعديل-البيانات-الشخصية)
   - [تغيير كلمة المرور](#33-تغيير-كلمة-المرور)
   - [حذف الحساب](#34-حذف-الحساب-ذاتياً)
4. [التصنيفات — Categories](#4-التصنيفات--categories)
5. [العلامات التجارية — Brands](#5-العلامات-التجارية--brands)
6. [المنتجات — Products](#6-المنتجات--products)
7. [البنرات — Banners](#7-البنرات--banners)
8. [الإشعارات — Notifications](#8-الإشعارات--notifications)
9. [أكواد الخطأ](#9-أكواد-الخطأ)

---

## 1. الملاحظات العامة

### المصادقة بالـ Token
المسارات المحمية تتطلب إرسال الـ token في الـ Header:
```
Authorization: Bearer {token}
```
الـ token يُستلم عند تسجيل الدخول أو إتمام التسجيل.

### هيكل الاستجابات
كل الاستجابات تأتي بالشكل:
```json
{
  "success": true | false,
  "message": "نص الرسالة",
  "data": { ... }
}
```

### الـ Pagination
كل endpoint يدعم pagination يُرجع:
```json
{
  "success": true,
  "data": [ ... ],
  "has_more": true,
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42
  }
}
```
للانتقال بين الصفحات أضف `?page=2` في الـ URL.  
يمكن تغيير حجم الصفحة بإضافة `?per_page=20` (الحد الأقصى 100).

### رقم الهاتف
- يجب أن يكون عراقياً صالحاً
- المدخل المقبول: `07XXXXXXXXX` أو `+9647XXXXXXXXX` (النظام يُحوّل تلقائياً إلى `+9647XXXXXXXXX`)

---

## 2. المصادقة — Auth

### 2.1 تسجيل الدخول

**POST** `/api/app/auth/login`

> ⚡ Rate Limit: 10 محاولات / دقيقة لكل IP

**Body:**
```json
{
  "phone": "07XXXXXXXXX",
  "password": "123456"
}
```

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "user": {
    "id": 1,
    "first_name": "أحمد",
    "last_name": "محمد",
    "full_name": "أحمد محمد",
    "phone": "+9647XXXXXXXXX",
    "gender": "male",
    "birth_date": "1995-06-15",
    "district_id": 2,
    "area_id": 5,
    "district": {
      "id": 2,
      "name": "بعقوبة"
    },
    "area": {
      "id": 5,
      "name": "الحسينية"
    },
    "is_active": true,
    "is_self_deleted": false,
    "created_at": "2025-01-20"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456"
}
```

**❌ خطأ — بيانات خاطئة (401):**
```json
{
  "success": false,
  "message": "رقم الهاتف أو كلمة المرور غير صحيحة."
}
```

**❌ خطأ — حساب محذوف ذاتياً (403):**
```json
{
  "success": false,
  "message": "تم حذف هذا الحساب بناءً على طلبك. تواصل مع الإدارة لاستعادته.",
  "code": "account_deleted"
}
```

**❌ خطأ — حساب معطّل إدارياً (403):**
```json
{
  "success": false,
  "message": "حسابك معطل حالياً من قبل الإدارة.",
  "code": "account_disabled"
}
```

> **ملاحظة:** عند تسجيل الدخول بنجاح، يتم إلغاء جميع الـ tokens السابقة (سياسة جهاز واحد).

---

### 2.2 التسجيل — إرسال OTP

**POST** `/api/app/auth/register/send-otp`

> ⚡ Rate Limit: 5 محاولات / دقيقة لكل IP + رقم هاتف

**Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "gender": "male",
  "birth_date": "1995-06-15",
  "district_id": 2,
  "area_id": 5,
  "phone": "07XXXXXXXXX",
  "password": "123456",
  "password_confirmation": "123456"
}
```

**قيم `gender`:** `male` | `female`  
**`birth_date`:** تاريخ قبل اليوم بصيغة `YYYY-MM-DD`  
**`district_id`:** يجب أن يكون موجوداً في جدول المقاطعات  
**`area_id`:** يجب أن ينتمي لنفس الـ `district_id` المُرسَل  
**`password`:** 6 أحرف على الأقل، مع تأكيد

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم إرسال رمز التحقق إلى واتساب بنجاح.",
  "registration_token": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
  "message_id": "MSG_XXXXXXXXXXXXXXXX"
}
```

> **مهم:** احتفظ بـ `registration_token` **و** `message_id` معاً — كلاهما مطلوب في الخطوة التالية. صلاحية الجلسة 10 دقائق.

**❌ خطأ — رقم مسجّل مسبقاً (422):**
```json
{
  "success": false,
  "errors": {
    "phone": ["هذا الرقم مسجّل مسبقاً."]
  }
}
```

**❌ خطأ — رقم حساب محذوف (422):**
```json
{
  "success": false,
  "message": "هذا الرقم مرتبط بحساب تم حذفه سابقاً. تواصل مع الإدارة لاستعادته.",
  "code": "account_deleted"
}
```

---

### 2.3 التسجيل — تأكيد OTP وإنشاء الحساب

**POST** `/api/app/auth/register/verify`

> ⚡ Rate Limit: 10 محاولات / دقيقة

**Body:**
```json
{
  "registration_token": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
  "message_id": "MSG_XXXXXXXXXXXXXXXX",
  "otp": "123456"
}
```

**✅ نجاح (201):**
```json
{
  "success": true,
  "message": "تم إنشاء حسابك بنجاح.",
  "user": {
    "id": 1,
    "first_name": "أحمد",
    "last_name": "محمد",
    "full_name": "أحمد محمد",
    "phone": "+9647XXXXXXXXX",
    "gender": "male",
    "birth_date": "1995-06-15",
    "district_id": 2,
    "area_id": 5,
    "district": { "id": 2, "name": "بعقوبة" },
    "area": { "id": 5, "name": "الحسينية" },
    "is_active": true,
    "is_self_deleted": false,
    "created_at": "2025-01-20"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456"
}
```

**❌ خطأ — OTP خاطئ (422):**
```json
{
  "success": false,
  "message": "رمز التحقق غير صحيح."
}
```

**❌ خطأ — Token منتهي (422):**
```json
{
  "success": false,
  "message": "انتهت صلاحية جلسة التسجيل. يرجى البدء من جديد."
}
```

---

### 2.4 نسيت كلمة المرور — إرسال OTP

**POST** `/api/app/auth/forgot-password/send-otp`

> ⚡ Rate Limit: 5 محاولات / دقيقة لكل IP + رقم هاتف

**Body:**
```json
{
  "phone": "07XXXXXXXXX"
}
```

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم إرسال رمز استعادة كلمة المرور بنجاح.",
  "message_id": "MSG_XXXXXXXXXXXXXXXX"
}
```

> **مهم:** احتفظ بـ `message_id` واستخدمه في الخطوة التالية.

**❌ خطأ — رقم غير موجود (422):**
```json
{
  "success": false,
  "message": "لا يوجد حساب مرتبط بهذا الرقم."
}
```

---

### 2.5 نسيت كلمة المرور — تأكيد وإعادة التعيين

**POST** `/api/app/auth/forgot-password/verify`

> ⚡ Rate Limit: 10 محاولات / دقيقة

**Body:**
```json
{
  "phone": "07XXXXXXXXX",
  "message_id": "MSG_XXXXXXXXXXXXXXXX",
  "otp": "123456",
  "password": "newpass123",
  "password_confirmation": "newpass123"
}
```

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم إعادة تعيين كلمة المرور بنجاح.",
  "user": { ... },
  "token": "2|abcdefghijklmnopqrstuvwxyz123456"
}
```

> عند إعادة التعيين بنجاح، تُلغى جميع الـ tokens القديمة ويُعاد تسجيل الدخول تلقائياً (token جديد في الاستجابة).

---

### 2.6 تسجيل الخروج

**POST** `/api/app/auth/logout`  
🔒 **يتطلب Token**

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج."
}
```

---

## 3. الملف الشخصي — Profile

> جميع endpoints هذا القسم 🔒 تتطلب Token  
> إذا كان الحساب معطلاً أو محذوفاً سيُرجع 403

---

### 3.1 عرض بيانات المستخدم

**GET** `/api/app/profile`

**✅ نجاح (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "first_name": "أحمد",
    "last_name": "محمد",
    "full_name": "أحمد محمد",
    "phone": "+9647XXXXXXXXX",
    "gender": "male",
    "birth_date": "1995-06-15",
    "district_id": 2,
    "area_id": 5,
    "district": { "id": 2, "name": "بعقوبة" },
    "area": { "id": 5, "name": "الحسينية" },
    "is_active": true,
    "is_self_deleted": false,
    "created_at": "2025-01-20"
  }
}
```

---

### 3.2 تعديل البيانات الشخصية

**PUT** `/api/app/profile`

> جميع الحقول اختيارية — أرسل فقط ما تريد تعديله

**Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "علي",
  "gender": "male",
  "birth_date": "1995-06-15",
  "district_id": 3,
  "area_id": 8
}
```

> **ملاحظة:** إذا أرسلت `area_id`، يجب أن ينتمي للـ `district_id` المُرسَل (أو الـ district الحالي للمستخدم إن لم يُرسَل).

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم تحديث بيانات ملفك الشخصي بنجاح.",
  "user": { ... }
}
```

---

### 3.3 تغيير كلمة المرور

**PUT** `/api/app/profile/password`

**Body:**
```json
{
  "current_password": "oldpass123",
  "new_password": "newpass456",
  "new_password_confirmation": "newpass456"
}
```

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم تغيير كلمة المرور بنجاح."
}
```

**❌ خطأ — كلمة المرور الحالية خاطئة (422):**
```json
{
  "success": false,
  "errors": {
    "current_password": ["كلمة المرور الحالية غير صحيحة."]
  }
}
```

---

### 3.4 حذف الحساب ذاتياً

**DELETE** `/api/app/profile`

> لا يُحذف الحساب نهائياً — يُعطَّل ويُسجَّل للمراجعة الإدارية.  
> بعد الحذف، يتم إلغاء جميع الـ tokens تلقائياً.

**✅ نجاح (200):**
```json
{
  "success": true,
  "message": "تم إيقاف حسابك بنجاح. يمكنك التواصل مع الإدارة لاستعادته."
}
```

---

## 4. التصنيفات — Categories

### 4.1 قائمة التصنيفات

**GET** `/api/app/categories`  
🔓 **لا يتطلب Token** — بيانات مؤقتة (Cached)

**✅ نجاح (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "كاسات",
      "slug": "glasses",
      "image_url": "https://wat.diyala.org/media/categories/glasses.png",
      "is_active": true,
      "sort_order": 1
    },
    {
      "id": 2,
      "name": "عبوات",
      "slug": "bottles",
      "image_url": null,
      "is_active": true,
      "sort_order": 2
    }
  ]
}
```

> مرتّبة تصاعدياً حسب `sort_order`.  
> تُرجع التصنيفات النشطة فقط.

---

## 5. العلامات التجارية — Brands

### 5.1 قائمة العلامات التجارية

**GET** `/api/app/brands`  
🔓 **لا يتطلب Token**

**Query Params:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | رقم الصفحة |
| `per_page` | int | 15 | عدد العناصر (max: 100) |

**✅ نجاح (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "دجلة",
      "slug": "dijla",
      "description": "مياه دجلة الصافية",
      "image_url": "https://wat.diyala.org/media/brands/dijla.png",
      "is_active": true,
      "sort_order": 1,
      "products_count": 5,
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "has_more": false,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 3
  },
  "links": { ... }
}
```

> تُرجع العلامات النشطة فقط، مرتّبة حسب `sort_order`.

---

### 5.2 منتجات علامة تجارية معينة

**GET** `/api/app/brands/{id}/products`  
🔓 **لا يتطلب Token**

**Query Params:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | رقم الصفحة |
| `per_page` | int | 15 | عدد العناصر (max: 100) |

**✅ نجاح (200):** نفس هيكل قائمة المنتجات (راجع قسم المنتجات).

> تُرجع المنتجات النشطة فقط لهذه العلامة.

---

## 6. المنتجات — Products

### 6.1 قائمة المنتجات

**GET** `/api/app/products`  
🔓 **لا يتطلب Token**

**Query Params:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | رقم الصفحة |
| `per_page` | int | 15 | عدد العناصر (max: 100) |
| `category_id` | int | — | فلترة حسب التصنيف |
| `brand_id` | int | — | فلترة حسب العلامة التجارية |
| `search` | string | — | بحث في الاسم والوصف |

**مثال:**
```
GET /api/app/products?category_id=1&brand_id=2&search=كاسة&page=1
```

**✅ نجاح (200):**
```json
{
  "data": [
    {
      "id": 10,
      "name": "كاسة دجلة 330 مل",
      "description": "كاسة مياه صافية 330 مل",
      "price": 500,
      "image_url": "https://wat.diyala.org/media/products/cup330.jpg",
      "is_active": true,
      "sort_order": 1,
      "category_id": 1,
      "brand_id": 1,
      "category": {
        "id": 1,
        "name": "كاسات",
        "slug": "glasses",
        "image_url": null,
        "is_active": true,
        "sort_order": 1
      },
      "brand": {
        "id": 1,
        "name": "دجلة",
        "slug": "dijla",
        "description": null,
        "image_url": null,
        "is_active": true,
        "sort_order": 1,
        "products_count": null,
        "created_at": "2025-01-01T00:00:00.000000Z"
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "has_more": true,
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 52
  },
  "links": { ... }
}
```

> تُرجع المنتجات النشطة فقط.

---

## 7. البنرات — Banners

### 7.1 قائمة البنرات

**GET** `/api/app/banners`  
🔓 **لا يتطلب Token**

**✅ نجاح (200):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "link",
      "url": "https://example.com/offer",
      "category_id": null,
      "product_id": null,
      "duration_type": "days",
      "duration_value": 7,
      "image_url": "https://wat.diyala.org/media/banners/banner1.jpg",
      "is_active": true,
      "sort_order": 1
    },
    {
      "id": 2,
      "type": "category",
      "url": null,
      "category_id": 3,
      "product_id": null,
      "duration_type": null,
      "duration_value": null,
      "image_url": "https://wat.diyala.org/media/banners/banner2.jpg",
      "is_active": true,
      "sort_order": 2
    },
    {
      "id": 3,
      "type": "none",
      "url": null,
      "category_id": null,
      "product_id": null,
      "duration_type": null,
      "duration_value": null,
      "image_url": "https://wat.diyala.org/media/banners/banner3.jpg",
      "is_active": true,
      "sort_order": 3
    }
  ]
}
```

**قيم `type` الممكنة:**
| type | الوصف |
|------|-------|
| `link` | يفتح رابط خارجي — الرابط في حقل `url` |
| `category` | يفتح صفحة تصنيف — الـ ID في حقل `category_id` |
| `product` | يفتح صفحة منتج — الـ ID في حقل `product_id` |
| `none` | بنر عرضي فقط، لا يؤدي أي إجراء |

**`duration_type`:** `hours` | `days` | `null` (مدة عرض البنر الاختيارية)

> تُرجع البنرات النشطة فقط، مرتّبة حسب `sort_order`.

---

## 8. الإشعارات — Notifications

### 8.1 قائمة الإشعارات

**GET** `/api/app/notifications`  
🔓 **لا يتطلب Token**

**Query Params:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | رقم الصفحة |
| `per_page` | int | 15 | عدد العناصر (max: 100) |

**✅ نجاح (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "title": "عرض خاص",
      "message": "احصل على خصم 20% على جميع المنتجات",
      "image_url": "https://wat.diyala.org/media/notifications/offer.jpg",
      "sent_at": "2025-04-08 10:30:00",
      "created_at": "2025-04-08 10:28:00"
    }
  ],
  "has_more": false,
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 8
  }
}
```

> تُرجع الإشعارات المُرسَلة فقط (`delivery_status = sent`)، مرتّبة من الأحدث للأقدم.

---

### 8.2 تفاصيل إشعار واحد

**GET** `/api/app/notifications/{id}`  
🔓 **لا يتطلب Token**

**✅ نجاح (200):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "عرض خاص",
    "message": "احصل على خصم 20% على جميع المنتجات اليوم فقط",
    "image_url": "https://wat.diyala.org/media/notifications/offer.jpg",
    "sent_at": "2025-04-08 10:30:00",
    "created_at": "2025-04-08 10:28:00"
  }
}
```

**❌ خطأ — غير موجود أو لم يُرسَل (404):**
```json
{
  "success": false,
  "message": "الإشعار غير متاح."
}
```

---

## 9. أكواد الخطأ

### HTTP Status Codes

| الكود | المعنى |
|-------|--------|
| `200` | طلب ناجح |
| `201` | تم الإنشاء بنجاح |
| `401` | غير مصادق — Token مفقود أو منتهي الصلاحية |
| `403` | ممنوع — حساب معطل أو محذوف |
| `404` | العنصر المطلوب غير موجود |
| `422` | بيانات غير صالحة — تحقق من حقل `errors` |
| `429` | تجاوز حد الطلبات — انتظر دقيقة |
| `500` | خطأ في السيرفر |

### أكواد الأعمال `code`

| الكود | المعنى |
|-------|--------|
| `account_deleted` | الحساب تم حذفه بطلب من المستخدم — اتصل بالإدارة |
| `account_disabled` | الحساب معطل من قِبَل الإدارة |

### هيكل خطأ الـ Validation (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "phone": ["هذا الرقم مسجّل مسبقاً."],
    "password": ["كلمة المرور يجب أن تكون 6 أحرف على الأقل."]
  }
}
```

---

## ملاحظات إضافية للمطوّر

### Firebase Push Notifications
التطبيق يستخدم Firebase لإرسال الإشعارات الفورية (Push).  
تأكد من تسجيل الـ FCM token في التطبيق واشتراكه في topic: `all_users`

### أرقام الهاتف
- المدخلات المقبولة: `07901234567` أو `+96479012345679`
- الاستجابة دائماً بالصيغة: `+964XXXXXXXXXX`

### الصور
- جميع الصور URLs كاملة تبدأ بـ `https://wat.diyala.org/media/...`
- قد تكون القيمة `null` إذا لم يكن هناك صورة

### الترتيب الافتراضي
- **التصنيفات:** `sort_order` تصاعدي
- **العلامات التجارية:** `sort_order` تصاعدي
- **المنتجات:** `sort_order` تصاعدي ثم `created_at` تنازلي
- **البنرات:** `sort_order` تصاعدي
- **الإشعارات:** `sent_at` تنازلي (الأحدث أولاً)

---

*آخر تحديث: 2026-04-08 | الإصدار: 1.0*
