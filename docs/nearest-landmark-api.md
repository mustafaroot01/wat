# Nearest Landmark API — أقرب نقطة دالة

## نظرة عامة

يتم حفظ **أقرب نقطة دالة** في بروفايل الزبون مرة واحدة عند التسجيل أو من صفحة الإعدادات.  
عند إنشاء طلب جديد، تُملأ تلقائياً من البروفايل — ويمكن للزبون تغييرها لكل طلب إن أراد.

---

## 1. التسجيل — `POST /api/app/auth/register/send-otp`

أضف `nearest_landmark` مع بيانات التسجيل (اختياري):

```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "gender": "male",
  "birth_date": "1995-06-15",
  "district_id": 2,
  "area_id": 5,
  "nearest_landmark": "قرب مسجد الرحمن",
  "phone": "07701234567",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

> `nearest_landmark` اختياري — يمكن إرساله فارغاً أو عدم إرساله.

---

## 2. عرض البروفايل — `GET /api/app/profile`

يرجع بيانات الزبون بما فيها `nearest_landmark`:

```json
{
  "success": true,
  "user": {
    "id": 1,
    "first_name": "أحمد",
    "last_name": "محمد",
    "phone": "07701234567",
    "district_id": 2,
    "area_id": 5,
    "nearest_landmark": "قرب مسجد الرحمن",
    "district": { "id": 2, "name": "الخالص" },
    "area": { "id": 5, "name": "حي السلام" }
  }
}
```

---

## 3. تحديث البروفايل — `PUT /api/app/profile`

يمكن تحديث `nearest_landmark` منفردةً أو مع بيانات أخرى:

```json
{
  "nearest_landmark": "بجانب محطة وقود الشرق"
}
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## 4. إنشاء طلب — `POST /api/app/orders`

### الحالة الأولى — الزبون لا يرسل `nearest_landmark`:
النقطة الدالة تُؤخذ تلقائياً من بروفايله.

```json
{
  "customer_name": "أحمد محمد",
  "customer_phone": "07701234567",
  "province": "ديالى",
  "district": "الخالص",
  "total_amount": 25000,
  "final_amount": 25000,
  "items": [...]
}
```

### الحالة الثانية — الزبون يرسل `nearest_landmark` مختلفة:
تُستخدم القيمة المرسلة وتتجاهل بروفايله.

```json
{
  "customer_name": "أحمد محمد",
  "customer_phone": "07701234567",
  "province": "ديالى",
  "district": "الخالص",
  "nearest_landmark": "عند دوار السيارات",
  "total_amount": 25000,
  "final_amount": 25000,
  "items": [...]
}
```

---

## تكامل Flutter

### 1. عرض النقطة الدالة في شاشة الطلب (مع إمكانية التعديل)

```dart
class OrderAddressSection extends StatefulWidget {
  final User user; // الزبون الحالي من البروفايل
  const OrderAddressSection({required this.user});

  @override
  State<OrderAddressSection> createState() => _OrderAddressSectionState();
}

class _OrderAddressSectionState extends State<OrderAddressSection> {
  late TextEditingController _landmarkController;

  @override
  void initState() {
    super.initState();
    // تُملأ تلقائياً من البروفايل
    _landmarkController = TextEditingController(
      text: widget.user.nearestLandmark ?? '',
    );
  }

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: _landmarkController,
      decoration: const InputDecoration(
        labelText: 'أقرب نقطة دالة',
        hintText: 'مثال: قرب مسجد الرحمن، بجانب محطة الوقود...',
        prefixIcon: Icon(Icons.location_on_outlined),
      ),
    );
  }

  // استخدم هذه القيمة عند إرسال الطلب
  String? get landmark => _landmarkController.text.trim().isEmpty
      ? null
      : _landmarkController.text.trim();
}
```

### 2. إرسال الطلب مع النقطة الدالة

```dart
Future<void> submitOrder() async {
  final body = {
    'customer_name':    user.fullName,
    'customer_phone':   user.phone,
    'province':         selectedProvince,
    'district':         selectedDistrict,
    'nearest_landmark': landmarkController.text.trim(), // من حقل الإدخال
    'total_amount':     totalAmount,
    'final_amount':     finalAmount,
    'items':            cartItems.map((i) => i.toJson()).toList(),
  };

  final res = await ApiService.post('/api/app/orders', body: body);
  // ...
}
```

### 3. تحديث النقطة الدالة من صفحة الإعدادات

```dart
Future<void> updateLandmark(String newLandmark) async {
  final res = await ApiService.put(
    '/api/app/profile',
    body: {'nearest_landmark': newLandmark},
  );

  if (res['success'] == true) {
    // حدّث الـ state المحلي
    setState(() => user.nearestLandmark = newLandmark);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('تم حفظ النقطة الدالة')),
    );
  }
}
```

---

## ملاحظات

| نقطة | التفاصيل |
|------|---------|
| **اختياري** | لا يُشترط إرساله في التسجيل أو الطلب |
| **الأولوية** | القيمة المرسلة مع الطلب تتقدم على البروفايل |
| **الحد الأقصى** | 255 حرف |
| **يظهر في الفاتورة** | نعم، يظهر في الفاتورة الورقية وفاتورة 58mm |
