# Store Status API — جدول الدوام الأسبوعي

## نظرة عامة

يتيح هذا النظام للمشرف تحديد أوقات عمل المتجر لكل يوم من أيام الأسبوع من لوحة التحكم. يقوم التطبيق بفحص حالة المتجر مرة واحدة عند الفتح ثم كل 5 دقائق.

---

## API Endpoint

### GET `/api/app/store-status`

لا يتطلب مصادقة (Public).

#### Response — مفتوح
```json
{
  "is_open": true,
  "message": "المتجر مفتوح"
}
```

#### Response — مغلق
```json
{
  "is_open": false,
  "message": "المتجر مغلق حالياً، يُرجى المحاولة خلال أوقات الدوام."
}
```

---

## آلية العمل في Backend

- يتحقق من اليوم الحالي (بتوقيت `Asia/Baghdad`).
- يبحث عن الجدول المحفوظ في `store_settings` تحت المفتاح `weekly_schedule`.
- إذا كان اليوم `enabled: false` → يُعيد `is_open: false`.
- إذا كان الوقت الحالي خارج نطاق `open`→`close` → يُعيد `is_open: false`.
- إذا لم يوجد جدول → يستخدم القيم القديمة `open_time` / `close_time` كـ fallback.

---

## تكامل Flutter

### 1. إنشاء `StoreStatusService`

أنشئ ملف `lib/services/store_status_service.dart`:

```dart
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class StoreStatusService extends ChangeNotifier {
  bool isOpen = true;
  String message = '';
  Timer? _timer;

  static const _url = 'https://your-domain.com/api/app/store-status';

  /// استدعه مرة واحدة عند بداية التطبيق
  Future<void> init() async {
    await _check();
    _timer = Timer.periodic(const Duration(minutes: 5), (_) => _check());
  }

  Future<void> _check() async {
    try {
      final res = await http.get(Uri.parse(_url));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        isOpen  = data['is_open']  ?? true;
        message = data['message']  ?? '';
        notifyListeners();
      }
    } catch (_) {
      // في حالة فشل الشبكة لا تغير الحالة الحالية
    }
  }

  /// استدعه يدوياً عند الرجوع للتطبيق من الخلفية
  Future<void> refresh() => _check();

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }
}
```

---

### 2. تسجيل الـ Service في `main.dart`

```dart
import 'package:provider/provider.dart';
import 'services/store_status_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final storeStatus = StoreStatusService();
  await storeStatus.init(); // ← فحص واحد عند الفتح

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: storeStatus),
        // ... باقي الـ providers
      ],
      child: const MyApp(),
    ),
  );
}
```

---

### 3. تحديث الحالة عند العودة للتطبيق من الخلفية

في `main.dart` أو `AppLifecycleObserver`:

```dart
class _MyAppState extends State<MyApp> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // تحديث عند رجوع التطبيق للواجهة
      context.read<StoreStatusService>().refresh();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }
}
```

---

### 4. عرض رسالة الإغلاق في الصفحة الرئيسية

```dart
import 'package:provider/provider.dart';

class HomePage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final store = context.watch<StoreStatusService>();

    return Scaffold(
      body: Column(
        children: [
          // بانر الإغلاق — يظهر فقط إذا المتجر مغلق
          if (!store.isOpen)
            Container(
              width: double.infinity,
              color: Colors.red.shade100,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(
                children: [
                  const Icon(Icons.storefront_outlined, color: Colors.red),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      store.message,
                      style: const TextStyle(
                        color: Colors.red,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),

          // باقي محتوى الصفحة
          Expanded(child: ProductsGrid()),
        ],
      ),
    );
  }
}
```

---

### 5. تعطيل زر الطلب إذا المتجر مغلق

```dart
final store = context.watch<StoreStatusService>();

ElevatedButton(
  // null = الزر معطّل تلقائياً في Flutter
  onPressed: store.isOpen ? () => _submitOrder() : null,
  child: Text(store.isOpen ? 'تأكيد الطلب' : 'المتجر مغلق حالياً'),
),
```

---

### 6. عرض حالة المتجر في شاشة الـ Checkout

```dart
Widget _buildStoreStatusBanner(StoreStatusService store) {
  if (store.isOpen) {
    return const SizedBox.shrink(); // لا شيء إذا مفتوح
  }
  return Container(
    margin: const EdgeInsets.only(bottom: 16),
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: Colors.red.shade50,
      borderRadius: BorderRadius.circular(8),
      border: Border.all(color: Colors.red.shade200),
    ),
    child: Row(
      children: [
        Icon(Icons.access_time, color: Colors.red.shade700),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            store.message,
            style: TextStyle(color: Colors.red.shade700),
          ),
        ),
      ],
    ),
  );
}
```

---

## ملاحظات مهمة

| نقطة | التفاصيل |
|------|---------|
| **التوقيت** | السيرفر يعتمد `Asia/Baghdad` (UTC+3) |
| **Fallback** | إذا فشل الاتصال، يبقى على آخر حالة محفوظة |
| **Backend Safety** | حتى لو التطبيق لم يفحص، السيرفر يرفض الطلب عند `POST /api/app/orders` |
| **التحديث** | كل 5 دقائق + عند فتح التطبيق + عند العودة من الخلفية |
| **الأيام بالإنجليزية** | `sunday, monday, tuesday, wednesday, thursday, friday, saturday` |
