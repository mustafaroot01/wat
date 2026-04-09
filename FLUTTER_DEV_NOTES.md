# 📱 ملاحظات تقنية لمطور Flutter
**تاريخ:** 2026-04-09  
**الخادم:** https://wat.diyala.org

---

## 1. التحقق من كود الخصم — `POST /api/app/coupons/validate`

### ⚠️ تغيير مهم: يتطلب الآن Authentication

هذا الـ endpoint أصبح **محمياً** ويتطلب Bearer Token.  
إذا لم يُرسَل الـ Token، سيرجع `401 Unauthorized`.

```http
POST /api/app/coupons/validate
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "SUMMER20",
  "order_amount": 30000
}
```

**Response ناجح:**
```json
{
  "success": true,
  "message": "كود الخصم صحيح!",
  "coupon_id": 2,
  "type": "percentage",
  "value": 10,
  "discount_amount": 3000,
  "final_amount": 27000
}
```

**Response فشل — مستخدم سابقاً:**
```json
{
  "success": false,
  "message": "لقد استخدمت هذا الكود مسبقاً."
}
```
> الـ status code في حالة الفشل هو `422`.

---

## 2. إنشاء طلب جديد — `POST /api/app/orders`

### ⚠️ يجب إرسال `coupon_id` إذا كان هناك كوبون

```http
POST /api/app/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_name": "أحمد محمد",
  "customer_phone": "07701234567",
  "province": "ديالى",
  "district": "بعقوبة",
  "nearest_landmark": "قرب مسجد النور",
  "notes": "يرجى التسليم في الصباح",

  "coupon_id": 2,
  "discount_amount": 3000,
  "total_amount": 30000,
  "final_amount": 27000,

  "items": [
    {
      "product_id": 1,
      "product_name": "كرتونة مياه دجلة 330 مل",
      "sku": "WAT-001",
      "unit_price": 15000,
      "quantity": 2,
      "total_price": 30000
    }
  ]
}
```

> إذا لم يكن هناك كوبون، لا ترسل `coupon_id` (أو أرسله `null`).

**Response ناجح `201`:**
```json
{
  "message": "تم إرسال الطلب بنجاح",
  "order": {
    "id": 15,
    "invoice_code": "INV-2025-0015",
    "invoice_token": "abc123xyz",
    "coupon_id": 2,
    ...
  }
}
```

---

## 3. تسجيل استخدام الكوبون — `POST /api/app/coupons/apply`

### ⚠️ يجب استدعاؤه بعد إنشاء الطلب مباشرة

هذه الخطوة **ضرورية** لـ:
- ظهور اسم الكوبون في فاتورة الزبون
- تسجيل الاستخدام في لوحة التحكم
- منع الزبون من استخدام الكوبون مرة ثانية

```http
POST /api/app/coupons/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "coupon_id": 2,
  "order_id": 15,
  "discount_amount": 3000
}
```

> `order_id` تأخذه من response إنشاء الطلب: `response['order']['id']`

**Response ناجح:**
```json
{
  "success": true,
  "message": "تم تطبيق كود الخصم بنجاح."
}
```

---

## 4. التدفق الكامل لعملية الشراء بكوبون

```
1. المستخدم يكتب كود الخصم
   ↓
2. POST /api/app/coupons/validate  ← مع Bearer Token
   ↓ إذا success: true
3. احتفظ بـ { coupon_id, discount_amount, final_amount }
   ↓
4. المستخدم يضغط "تأكيد الطلب"
   ↓
5. POST /api/app/orders  ← مع coupon_id + discount_amount + final_amount
   ↓ إذا نجح (201)
6. احتفظ بـ order_id من الـ response
   ↓
7. POST /api/app/coupons/apply  ← مع coupon_id + order_id + discount_amount
   ↓
8. اعرض رسالة النجاح للمستخدم
```

---

## 5. أكواد الأخطاء المتوقعة

| Code | السبب | الحل |
|------|-------|------|
| `401` | لم يُرسَل Bearer Token | تأكد من إرسال Authorization header |
| `422` | كود الخصم مستخدم مسبقاً | اعرض رسالة `response.message` للمستخدم |
| `422` | الكوبون منتهي الصلاحية | اعرض رسالة `response.message` |
| `422` | المبلغ أقل من الحد الأدنى للطلب | اعرض رسالة `response.message` |

---

## 6. ملاحظات عامة

- كل endpoint محمي يتطلب: `Authorization: Bearer {token}`
- جميع الطلبات بـ `Content-Type: application/json`
- في حالة خطأ الـ validation (`422`)، الرسالة موجودة في:
  - `response['message']` — للأخطاء العامة
  - `response['errors']` — لأخطاء الحقول التفصيلية
- كود الخصم يُسمح باستخدامه **مرة واحدة فقط** لكل حساب

---

> للاستفسار: تواصل مع مدير المشروع.
