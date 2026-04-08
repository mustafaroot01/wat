# 📱 توثيق API — تطبيق معمل امواج ديالى

> **Base URL:** `https://wat.diyala.org/api/app`  
> **Content-Type:** `application/json`  
> **Accept:** `application/json`

---

## 📋 جدول المحتويات

1. [المصادقة — Auth](#1-المصادقة--auth)
2. [البانرات — Banners](#2-البانرات--banners)
3. [التصنيفات — Categories](#3-التصنيفات--categories)
4. [الشركات / البراندات — Brands](#4-الشركات--brands)
5. [المنتجات — Products](#5-المنتجات--products)
6. [الأقضية والمناطق — Districts & Areas](#6-الأقضية-والمناطق--districts--areas)
7. [الإشعارات والأخبار — Notifications](#7-الإشعارات-والأخبار--notifications)
8. [الكوبونات / الخصومات — Coupons](#8-الكوبونات--coupons)
9. [الطلبات — Orders](#9-الطلبات--orders)
10. [الملف الشخصي — Profile](#10-الملف-الشخصي--profile)
11. [رموز الحالات](#11-رموز-الحالات)
12. [رموز الأخطاء الشائعة](#12-رموز-الأخطاء-الشائعة)

---

## 🔐 المصادقة في الـ Requests

كل endpoint يحتاج تسجيل دخول يُرسل معه الـ token في الـ Header:

```
Authorization: Bearer {token}
```

---

## 1. المصادقة — Auth

### 1.1 تسجيل الدخول

```
POST /api/app/auth/login
```

**Body:**
```json
{
  "phone": "07701234567",
  "password": "secret123"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح.",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "first_name": "أحمد",
    "last_name": "محمد",
    "full_name": "أحمد محمد",
    "phone": "9647701234567",
    "gender": "male",
    "birth_date": "1995-06-15",
    "district_id": 2,
    "area_id": 5,
    "district": { "id": 2, "name": "بعقوبة" },
    "area": { "id": 5, "name": "الحي الصناعي" },
    "orders_count": 3,
    "is_active": true,
    "is_self_deleted": false,
    "created_at": "2025-01-10 14:30:00"
  }
}
```

**Response 401 — بيانات خاطئة:**
```json
{
  "success": false,
  "message": "رقم الهاتف أو كلمة المرور غير صحيحة."
}
```

**Response 403 — حساب معطل:**
```json
{
  "success": false,
  "message": "حسابك معطل حالياً من قبل الإدارة.",
  "code": "account_disabled"
}
```

> ⚠️ الرقم يُقبل بأي صيغة: `07701234567` أو `7701234567` أو `9647701234567`

---

### 1.2 التسجيل — الخطوة 1: إرسال OTP

```
POST /api/app/auth/register/send-otp
```

**Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "gender": "male",
  "birth_date": "1995-06-15",
  "district_id": 2,
  "area_id": 5,
  "phone": "07701234567",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "تم إرسال رمز التحقق إلى واتساب بنجاح.",
  "registration_token": "xYz7abc...",
  "message_id": "msg_abc123"
}
```

> 📌 احفظ `registration_token` و `message_id` — ستحتاجهم في الخطوة 2

---

### 1.3 التسجيل — الخطوة 2: التحقق وإنشاء الحساب

```
POST /api/app/auth/register/verify
```

**Body:**
```json
{
  "registration_token": "xYz7abc...",
  "otp": "123456",
  "message_id": "msg_abc123"
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "أهلاً بك! تم إنشاء حسابك بنجاح.",
  "token": "2|xyz789...",
  "user": { ... }
}
```

---

### 1.4 نسيت كلمة المرور — الخطوة 1: إرسال OTP

```
POST /api/app/auth/forgot-password/send-otp
```

**Body:**
```json
{
  "phone": "07701234567"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "تم إرسال رمز استعادة كلمة المرور بنجاح.",
  "message_id": "msg_def456"
}
```

---

### 1.5 نسيت كلمة المرور — الخطوة 2: التحقق وتعيين الباسورد

```
POST /api/app/auth/forgot-password/verify
```

**Body:**
```json
{
  "phone": "07701234567",
  "message_id": "msg_def456",
  "otp": "654321",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "تم تغيير كلمة المرور بنجاح، يمكنك الدخول الآن.",
  "token": "3|newtoken...",
  "user": { ... }
}
```

---

### 1.6 تسجيل الخروج 🔒

```
POST /api/app/auth/logout
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج."
}
```

---

## 2. البانرات — Banners

### 2.1 جلب البانرات النشطة

```
GET /api/app/banners
```

**Response 200:**
```json
[
  {
    "id": 1,
    "title": "عرض رمضان",
    "image_url": "https://wat.diyala.org/media/banners/banner1.jpg",
    "type": "product",
    "product_id": 12,
    "category_id": null,
    "url": null,
    "sort_order": 1,
    "is_active": true
  },
  {
    "id": 2,
    "title": "تصفح تصنيفاتنا",
    "image_url": "https://wat.diyala.org/media/banners/banner2.jpg",
    "type": "category",
    "product_id": null,
    "category_id": 3,
    "url": null,
    "sort_order": 2,
    "is_active": true
  }
]
```

**أنواع البانر (`type`):**

| القيمة | الوصف |
|--------|-------|
| `none` | بانر عادي بدون رابط |
| `product` | يفتح صفحة منتج → استخدم `product_id` |
| `category` | يفتح تصنيف → استخدم `category_id` |
| `link` | رابط خارجي → استخدم `url` |

---

## 3. التصنيفات — Categories

### 3.1 جلب التصنيفات

```
GET /api/app/categories
```

**Response 200:**
```json
[
  {
    "id": 1,
    "name": "مياه بيت",
    "image_url": "https://wat.diyala.org/media/categories/cat1.jpg",
    "sort_order": 1,
    "is_active": true
  },
  {
    "id": 2,
    "name": "عبوات صغيرة",
    "image_url": null,
    "sort_order": 2,
    "is_active": true
  }
]
```

---

### 3.2 جلب فلاتر تصنيف معين

```
GET /api/app/categories/{category_id}/filters
```

**Response 200:**
```json
[
  { "id": 1, "name": "330 مل", "category_id": 1, "sort_order": 1 },
  { "id": 2, "name": "500 مل", "category_id": 1, "sort_order": 2 },
  { "id": 3, "name": "1.5 لتر", "category_id": 1, "sort_order": 3 }
]
```

---

## 4. الشركات / Brands

### 4.1 جلب البراندات

```
GET /api/app/brands
```

**Response 200:**
```json
[
  {
    "id": 1,
    "name": "دجلة",
    "image_url": "https://wat.diyala.org/media/brands/dijla.png",
    "is_active": true
  },
  {
    "id": 2,
    "name": "الريان",
    "image_url": "https://wat.diyala.org/media/brands/rayan.png",
    "is_active": true
  }
]
```

---

### 4.2 جلب منتجات براند معين

```
GET /api/app/brands/{brand_id}/products
```

**Response:** نفس هيكل قائمة المنتجات (انظر قسم المنتجات)

---

## 5. المنتجات — Products

### 5.1 جلب قائمة المنتجات

```
GET /api/app/products
```

**Query Parameters (اختياري):**

| Parameter | Type | الوصف |
|-----------|------|-------|
| `category_id` | integer | فلترة حسب التصنيف |
| `brand_id` | integer | فلترة حسب البراند |
| `filter_id` | integer | فلترة حسب الفلتر (الحجم مثلاً) |
| `in_stock` | boolean | `true` للمتوفرة فقط |
| `search` | string | بحث باسم المنتج أو SKU |
| `page` | integer | رقم الصفحة (default: 1) |
| `per_page` | integer | عدد في الصفحة (default: 15) |

**مثال:**
```
GET /api/app/products?category_id=1&in_stock=true&per_page=20
```

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "sku": "WAT-001",
      "name": "كرتونة مياه دجلة 330 مل",
      "description": "كرتونة تحتوي على 24 حبة",
      "price": 5000.00,
      "discount_percentage": 10,
      "discounted_price": 4500.00,
      "has_discount": true,
      "in_stock": true,
      "image_url": "https://wat.diyala.org/media/products/product1.jpg",
      "is_active": true,
      "sort_order": 1,
      "created_at": "2025-01-01 12:00:00",
      "category": {
        "id": 1,
        "name": "مياه بيت"
      },
      "brand": {
        "id": 1,
        "name": "دجلة",
        "image_url": "https://wat.diyala.org/media/brands/dijla.png"
      },
      "filter": {
        "id": 1,
        "name": "330 مل"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

> 💡 **منطق السعر في التطبيق:**
> - إذا `has_discount = true` → اعرض `discounted_price` كسعر نهائي و `price` كسعر قديم مشطوب
> - إذا `has_discount = false` → اعرض `price` فقط
> - إذا `in_stock = false` → اعرض "نفذت الكمية" وعطّل زر الإضافة للسلة

---

### 5.2 إضافة منتج للسلة (منطق السلة — Frontend Only)

> ⚠️ السلة تُدار **في التطبيق فقط** (Local State / SQLite) — لا يوجد Cart API.
> عند الطلب يُرسل محتوى السلة كاملاً.

**هيكل عنصر السلة المقترح:**
```json
{
  "product_id": 1,
  "product_name": "كرتونة مياه دجلة 330 مل",
  "sku": "WAT-001",
  "unit_price": 4500.00,
  "quantity": 2,
  "total_price": 9000.00
}
```

---

## 6. الأقضية والمناطق — Districts & Areas

### 6.1 جلب الأقضية

```
GET /api/app/districts
```

**Response 200:**
```json
[
  { "id": 1, "name": "بعقوبة" },
  { "id": 2, "name": "المقدادية" },
  { "id": 3, "name": "خانقين" }
]
```

---

### 6.2 جلب مناطق قضاء معين

```
GET /api/app/districts/{district_id}/areas
```

**مثال:**
```
GET /api/app/districts/1/areas
```

**Response 200:**
```json
[
  { "id": 1, "name": "الحي الصناعي", "district_id": 1 },
  { "id": 2, "name": "حي النهضة", "district_id": 1 },
  { "id": 3, "name": "المنطقة الشرقية", "district_id": 1 }
]
```

> 💡 **الاستخدام في التسجيل:**
> 1. جلب الأقضية وعرضها في Dropdown الأول
> 2. عند اختيار قضاء → جلب مناطقه وعرضها في Dropdown الثاني
> 3. إرسال `district_id` و `area_id` في طلب التسجيل

---

## 7. الإشعارات والأخبار — Notifications

### 7.1 جلب قائمة الإشعارات

```
GET /api/app/notifications
```

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "عرض خاص هذا الأسبوع!",
      "body": "احصل على خصم 15% على جميع المياه المعبأة",
      "image_url": "https://wat.diyala.org/media/notifications/notif1.jpg",
      "created_at": "2025-04-01 10:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 15,
    "total": 20
  }
}
```

---

### 7.2 تفاصيل إشعار واحد

```
GET /api/app/notifications/{id}
```

**Response 200:**
```json
{
  "id": 1,
  "title": "عرض خاص هذا الأسبوع!",
  "body": "احصل على خصم 15% على جميع المياه المعبأة. العرض ساري حتى نهاية الأسبوع.",
  "image_url": "https://wat.diyala.org/media/notifications/notif1.jpg",
  "created_at": "2025-04-01 10:00:00"
}
```

---

## 8. الكوبونات / الخصومات — Coupons

### 8.1 التحقق من كوبون (بدون تسجيل دخول)

```
POST /api/app/coupons/validate
```

**Body:**
```json
{
  "code": "SUMMER20",
  "order_amount": 25000
}
```

**Response 200 — كوبون صالح:**
```json
{
  "valid": true,
  "coupon": {
    "id": 3,
    "code": "SUMMER20",
    "type": "percentage",
    "value": 20,
    "min_order_amount": 10000,
    "discount_amount": 5000
  }
}
```

**Response 422 — كوبون غير صالح:**
```json
{
  "valid": false,
  "message": "هذا الكوبون منتهي الصلاحية."
}
```

**أنواع الكوبون (`type`):**

| القيمة | الحساب |
|--------|--------|
| `percentage` | خصم نسبة مئوية من المبلغ → `discount = amount * value / 100` |
| `fixed` | خصم مبلغ ثابت → `discount = value` |

---

### 8.2 تطبيق الكوبون بعد الطلب 🔒

```
POST /api/app/coupons/apply
Authorization: Bearer {token}
```

**Body:**
```json
{
  "code": "SUMMER20",
  "order_id": 15
}
```

---

## 9. الطلبات — Orders

### 9.1 إنشاء طلب جديد 🔒

```
POST /api/app/orders
Authorization: Bearer {token}
```

**Body:**
```json
{
  "customer_name": "أحمد محمد",
  "customer_phone": "07701234567",
  "province": "ديالى",
  "district": "بعقوبة",
  "nearest_landmark": "قرب مسجد النور",
  "notes": "يرجى التسليم في الصباح",
  "coupon_id": 3,
  "discount_amount": 5000,
  "total_amount": 25000,
  "final_amount": 20000,
  "items": [
    {
      "product_id": 1,
      "product_name": "كرتونة مياه دجلة 330 مل",
      "sku": "WAT-001",
      "unit_price": 4500,
      "quantity": 2,
      "total_price": 9000
    },
    {
      "product_id": 3,
      "product_name": "عبوة الريان 5 لتر",
      "sku": "WAT-003",
      "unit_price": 8000,
      "quantity": 2,
      "total_price": 16000
    }
  ]
}
```

**Response 201:**
```json
{
  "message": "تم إرسال الطلب بنجاح",
  "order": {
    "id": 15,
    "invoice_code": "INV-2025-0015",
    "invoice_token": "abc123xyz...",
    "status": "sent",
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "province": "ديالى",
    "district": "بعقوبة",
    "nearest_landmark": "قرب مسجد النور",
    "notes": "يرجى التسليم في الصباح",
    "total_amount": 25000,
    "discount_amount": 5000,
    "final_amount": 20000,
    "coupon_id": 3,
    "created_at": "2025-04-08 14:30:00",
    "items": [
      {
        "id": 31,
        "product_id": 1,
        "product_name": "كرتونة مياه دجلة 330 مل",
        "sku": "WAT-001",
        "unit_price": 4500,
        "quantity": 2,
        "total_price": 9000
      }
    ]
  }
}
```

---

### 9.2 قائمة طلباتي 🔒

```
GET /api/app/orders
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | الوصف |
|-----------|------|-------|
| `page` | integer | رقم الصفحة |
| `per_page` | integer | عدد في الصفحة (default: 15) |

**Response 200:**
```json
{
  "data": [
    {
      "id": 15,
      "invoice_code": "INV-2025-0015",
      "status": "out_for_delivery",
      "final_amount": 20000,
      "created_at": "2025-04-08 14:30:00",
      "items": [ ... ]
    },
    {
      "id": 10,
      "invoice_code": "INV-2025-0010",
      "status": "delivered",
      "final_amount": 15000,
      "created_at": "2025-03-20 09:00:00",
      "items": [ ... ]
    }
  ],
  "total": 5,
  "current_page": 1,
  "last_page": 1
}
```

---

### 9.3 تفاصيل طلب واحد 🔒

```
GET /api/app/orders/{id}
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "order": {
    "id": 15,
    "invoice_code": "INV-2025-0015",
    "invoice_token": "abc123xyz...",
    "status": "out_for_delivery",
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "province": "ديالى",
    "district": "بعقوبة",
    "nearest_landmark": "قرب مسجد النور",
    "notes": "يرجى التسليم في الصباح",
    "total_amount": 25000,
    "discount_amount": 5000,
    "final_amount": 20000,
    "rejection_reason": null,
    "created_at": "2025-04-08 14:30:00",
    "coupon": {
      "id": 3,
      "code": "SUMMER20",
      "type": "percentage",
      "value": 20
    },
    "items": [
      {
        "id": 31,
        "product_id": 1,
        "product_name": "كرتونة مياه دجلة 330 مل",
        "sku": "WAT-001",
        "unit_price": 4500,
        "quantity": 2,
        "total_price": 9000,
        "product": {
          "id": 1,
          "image_url": "https://wat.diyala.org/media/products/product1.jpg"
        }
      }
    ]
  }
}
```

---

### 9.4 إلغاء طلب 🔒

```
PATCH /api/app/orders/{id}/cancel
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "message": "تم إلغاء الطلب"
}
```

**Response 422 — لا يمكن الإلغاء:**
```json
{
  "message": "لا يمكن إلغاء الطلب في هذه المرحلة"
}
```

> ⚠️ الإلغاء متاح فقط عندما تكون الحالة `sent` (تم الإرسال)

---

## 10. الملف الشخصي — Profile

### 10.1 جلب بيانات الحساب 🔒

```
GET /api/app/profile
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "id": 1,
  "first_name": "أحمد",
  "last_name": "محمد",
  "full_name": "أحمد محمد",
  "phone": "9647701234567",
  "gender": "male",
  "birth_date": "1995-06-15",
  "district_id": 2,
  "area_id": 5,
  "district": { "id": 2, "name": "بعقوبة" },
  "area": { "id": 5, "name": "الحي الصناعي" },
  "is_active": true,
  "created_at": "2025-01-10 14:30:00"
}
```

---

### 10.2 تعديل بيانات الحساب 🔒

```
PUT /api/app/profile
Authorization: Bearer {token}
```

**Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "علي",
  "gender": "male",
  "birth_date": "1995-06-15",
  "district_id": 2,
  "area_id": 5
}
```

---

### 10.3 تغيير كلمة المرور 🔒

```
PUT /api/app/profile/password
Authorization: Bearer {token}
```

**Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

---

### 10.4 حذف الحساب 🔒

```
DELETE /api/app/profile
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "message": "تم حذف حسابك بنجاح."
}
```

---

## 11. رموز الحالات

### حالات الطلب (`status`)

| الرمز | العربي | متى يظهر |
|-------|--------|----------|
| `sent` | تم الإرسال | عند إنشاء الطلب |
| `received_preparing` | جاري التجهيز | قبول الإدارة وبدء التجهيز |
| `out_for_delivery` | جاري التوصيل | خرج للتوصيل |
| `delivered` | تم التسليم | وصل للزبون |
| `rejected` | مرفوض | رفضه الإدارة (يوجد `rejection_reason`) |
| `cancelled` | ملغي | ألغاه الزبون |

> 💡 الزبون يستطيع الإلغاء فقط عند حالة `sent`

### الجنس (`gender`)

| الرمز | المعنى |
|-------|--------|
| `male` | ذكر |
| `female` | أنثى |

---

## 12. رموز الأخطاء الشائعة

| HTTP Code | المعنى |
|-----------|--------|
| `200` | ✅ نجاح |
| `201` | ✅ تم الإنشاء |
| `401` | ❌ غير مسجل دخول أو token خاطئ |
| `403` | ❌ حساب معطل أو غير مصرح |
| `404` | ❌ السجل غير موجود |
| `422` | ❌ خطأ في البيانات المُرسلة |
| `429` | ⏳ طلبات كثيرة — انتظر قليلاً |
| `500` | 💥 خطأ في السيرفر |

**هيكل خطأ الـ Validation (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "phone": ["رقم الهاتف مطلوب."],
    "password": ["كلمة المرور يجب أن تكون 6 أحرف على الأقل."]
  }
}
```

---

## 📝 ملاحظات للمطور

1. **السلة (Cart):** تُدار محلياً في التطبيق — لا يوجد cart API. عند إنشاء الطلب، أرسل محتوى السلة كاملاً في `items`.

2. **الصور:** جميع URLs للصور كاملة تبدأ بـ `https://` — لا تحتاج إضافة Base URL.

3. **الأرقام:** أرقام الهواتف تُرسل بأي صيغة عراقية وتُعيد محصّلة بصيغة دولية `964XXXXXXXXXX`.

4. **Pagination:** جميع القوائم تدعم `?page=1&per_page=15`.

5. **Auto-refresh للطلبات:** استخدم polling كل 30 ثانية على `GET /api/app/orders/{id}` لتحديث الحالة.

6. **تسجيل الدخول على جهاز واحد:** عند تسجيل الدخول يُلغى Token كل الأجهزة الأخرى تلقائياً.

7. **OTP:** يُرسل عبر WhatsApp (خدمة Rassal) — صالح لمدة محدودة.

---

*آخر تحديث: أبريل 2026 — معمل امواج ديالى*
