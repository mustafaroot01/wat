# 📲 Notifications API — توثيق كامل لمطور التطبيق

**Base URL:** `https://wat.diyala.org/api/app`  
**Auth:** Bearer Token (يُرسل في كل طلب محمي)

---

## 1. حفظ FCM Token (مطلوب عند كل تسجيل دخول)

> يجب استدعاء هذا الـ endpoint بعد تسجيل الدخول مباشرةً لكي تصل الإشعارات للجهاز.

**POST** `/api/app/fcm-token`  
🔒 يتطلب: `Authorization: Bearer {token}`

### Request Body
```json
{
  "token": "d7k2p...FCM_DEVICE_TOKEN"
}
```

### Response — نجاح ✅
```json
{
  "success": true
}
```

### Response — خطأ ❌
```json
{
  "success": false,
  "message": "The token field is required."
}
```

### Flutter Code
```dart
Future<void> saveFcmToken(String bearerToken) async {
  final fcmToken = await FirebaseMessaging.instance.getToken();
  if (fcmToken == null) return;

  await http.post(
    Uri.parse('https://wat.diyala.org/api/app/fcm-token'),
    headers: {
      'Authorization': 'Bearer $bearerToken',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({'token': fcmToken}),
  );

  // تحديث التوكن لو تجدد تلقائياً
  FirebaseMessaging.instance.onTokenRefresh.listen((newToken) async {
    await http.post(
      Uri.parse('https://wat.diyala.org/api/app/fcm-token'),
      headers: {
        'Authorization': 'Bearer $bearerToken',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'token': newToken}),
    );
  });
}
```

---

## 2. قائمة الإشعارات

**GET** `/api/app/notifications`  
🌐 لا يتطلب Auth

### Query Parameters (اختياري)
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | رقم الصفحة |
| `per_page` | int | 15 | عدد الإشعارات في الصفحة |

### Request Example
```
GET https://wat.diyala.org/api/app/notifications?page=1&per_page=15
```

### Response — نجاح ✅
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "title": "عرض خاص! 🎉",
      "message": "خصم 20% على جميع المنتجات اليوم فقط",
      "image_url": "https://wat.diyala.org/media/notifications/abc123.jpg",
      "sent_at": "2026-04-10 01:30:00",
      "created_at": "2026-04-10 01:29:55"
    },
    {
      "id": 4,
      "title": "طلبك في الطريق 🚗",
      "message": "طلبك رقم #1042 خرج للتوصيل",
      "image_url": null,
      "sent_at": "2026-04-09 18:00:00",
      "created_at": "2026-04-09 17:59:40"
    }
  ],
  "has_more": true,
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42
  }
}
```

### Flutter Code
```dart
Future<List<AppNotification>> fetchNotifications({int page = 1}) async {
  final response = await http.get(
    Uri.parse('https://wat.diyala.org/api/app/notifications?page=$page&per_page=15'),
    headers: {'Accept': 'application/json'},
  );

  final data = jsonDecode(response.body);
  if (data['success'] == true) {
    return (data['data'] as List)
        .map((n) => AppNotification.fromJson(n))
        .toList();
  }
  return [];
}
```

---

## 3. تفاصيل إشعار واحد

**GET** `/api/app/notifications/{id}`  
🌐 لا يتطلب Auth

### Request Example
```
GET https://wat.diyala.org/api/app/notifications/5
```

### Response — نجاح ✅
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "عرض خاص! 🎉",
    "message": "خصم 20% على جميع المنتجات اليوم فقط",
    "image_url": "https://wat.diyala.org/media/notifications/abc123.jpg",
    "sent_at": "2026-04-10 01:30:00",
    "created_at": "2026-04-10 01:29:55"
  }
}
```

### Response — إشعار غير موجود ❌
```json
{
  "success": false,
  "message": "الإشعار غير متاح."
}
```

---

## 4. إعداد FCM في Flutter (إعداد أولي كامل)

### pubspec.yaml
```yaml
dependencies:
  firebase_core: ^3.x.x
  firebase_messaging: ^15.x.x
  flutter_local_notifications: ^17.x.x
```

### main.dart
```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  
  // إعداد معالج الخلفية
  FirebaseMessaging.onBackgroundMessage(_firebaseBackgroundHandler);
  
  runApp(MyApp());
}

@pragma('vm:entry-point')
Future<void> _firebaseBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  // يتم معالجة الإشعار تلقائياً في الخلفية
}
```

### notification_service.dart
```dart
class NotificationService {
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  static Future<void> init() async {
    // طلب الإذن
    await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // إعداد Local Notifications لعرض الإشعار عندما يكون التطبيق مفتوح
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosInit = DarwinInitializationSettings();
    await _localNotifications.initialize(
      const InitializationSettings(android: androidInit, iOS: iosInit),
    );

    // إنشاء Channel لـ Android
    const channel = AndroidNotificationChannel(
      'high_importance_channel', // ← نفس channel_id المرسل من السيرفر
      'إشعارات عامة',
      importance: Importance.high,
      playSound: true,
    );
    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);

    // عرض الإشعار عندما يكون التطبيق مفتوح (Foreground)
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      if (message.notification != null) {
        _localNotifications.show(
          message.hashCode,
          message.notification!.title,
          message.notification!.body,
          const NotificationDetails(
            android: AndroidNotificationDetails(
              'high_importance_channel',
              'إشعارات عامة',
              importance: Importance.high,
              priority: Priority.high,
              playSound: true,
            ),
            iOS: DarwinNotificationDetails(sound: 'default'),
          ),
        );
      }
    });
  }
}
```

---

## 5. نموذج البيانات (Dart Model)

```dart
class AppNotification {
  final int id;
  final String title;
  final String message;
  final String? imageUrl;
  final DateTime? sentAt;
  final DateTime createdAt;

  AppNotification({
    required this.id,
    required this.title,
    required this.message,
    this.imageUrl,
    this.sentAt,
    required this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id:        json['id'],
      title:     json['title'],
      message:   json['message'],
      imageUrl:  json['image_url'],
      sentAt:    json['sent_at'] != null ? DateTime.parse(json['sent_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}
```

---

## 6. ملخص الـ Endpoints

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| `POST` | `/api/app/fcm-token` | ✅ Bearer | حفظ FCM token للجهاز |
| `GET` | `/api/app/notifications` | ❌ | قائمة الإشعارات |
| `GET` | `/api/app/notifications/{id}` | ❌ | تفاصيل إشعار |

---

## 7. ملاحظات مهمة

- **`channel_id`** في الكود Flutter يجب أن يكون `high_importance_channel` (نفس ما يرسله السيرفر)
- **FCM Token** يجب إرساله بعد كل تسجيل دخول وعند تجديده تلقائياً
- الإشعارات تُعرض تلقائياً عند الخلفية/الإغلاق — فقط Foreground يحتاج `flutter_local_notifications`
- `has_more: true` تعني توجد صفحات إضافية — استخدم `page` للـ pagination
