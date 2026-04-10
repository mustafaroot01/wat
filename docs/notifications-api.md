# 📲 API Documentation — توثيق كامل لمطور التطبيق

**Base URL:** `https://wat.diyala.org/api/app`

---

## ⚡ الإعداد الأساسي (سطر واحد يكفي)

```dart
// في main.dart — بعد initializeApp مباشرةً
await FirebaseMessaging.instance.subscribeToTopic('all_users');
```

> السيرفر يرسل الإشعارات لـ Topic `all_users` — هذا السطر يجعل الجهاز يستقبلها تلقائياً بدون أي login أو token.

---

## Endpoints

### 1. قائمة الإشعارات

**GET** `/api/app/notifications`  
🌐 لا يتطلب Auth

**Query Parameters:**

| Parameter | Type | Default |
|-----------|------|---------|
| `page` | int | 1 |
| `per_page` | int | 15 |

**Response:**
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

---

### 2. تفاصيل إشعار واحد

**GET** `/api/app/notifications/{id}`  
🌐 لا يتطلب Auth

**Response نجاح:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "عرض خاص! 🎉",
    "message": "خصم 20% على جميع المنتجات اليوم فقط",
    "image_url": null,
    "sent_at": "2026-04-10 01:30:00",
    "created_at": "2026-04-10 01:29:55"
  }
}
```

**Response خطأ:**
```json
{ "success": false, "message": "الإشعار غير متاح." }
```

---

### 3. حفظ FCM Token (اختياري — backup إضافي)

> **ليس ضرورياً** إذا التطبيق مشترك بالـ Topic. يُستخدم كطبقة إضافية فقط.

**POST** `/api/app/fcm-token`  
🔒 يتطلب: `Authorization: Bearer {token}`

**Body:**
```json
{ "token": "FCM_DEVICE_TOKEN" }
```

**Response:**
```json
{ "success": true }
```

---

## إعداد Flutter الكامل

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
  FirebaseMessaging.onBackgroundMessage(_bgHandler);
  runApp(MyApp());
}

@pragma('vm:entry-point')
Future<void> _bgHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}
```

### notification_service.dart
```dart
class NotificationService {
  static final _local = FlutterLocalNotificationsPlugin();

  static Future<void> init() async {
    // 1. طلب الإذن
    await FirebaseMessaging.instance.requestPermission(
      alert: true, badge: true, sound: true,
    );

    // 2. الاشتراك في topic الإشعارات العامة
    await FirebaseMessaging.instance.subscribeToTopic('all_users');

    // 3. إنشاء Android Channel
    await _local
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(const AndroidNotificationChannel(
          'high_importance_channel', // ← يجب أن يطابق هذا بالضبط
          'إشعارات عامة',
          importance: Importance.high,
          playSound: true,
        ));

    // 4. إعداد Local Notifications
    await _local.initialize(const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    ));

    // 5. عرض الإشعار عندما يكون التطبيق مفتوح (Foreground) مع دعم الصور
    FirebaseMessaging.onMessage.listen((message) async {
      if (message.notification == null) return;
      final imageUrl = message.notification!.android?.imageUrl
                    ?? message.notification!.apple?.imageUrl;
      await _showNotification(message, imageUrl);
    });
  }

  static Future<void> _showNotification(
    RemoteMessage message,
    String? imageUrl,
  ) async {
    AndroidNotificationDetails androidDetails;

    if (imageUrl != null && imageUrl.isNotEmpty) {
      // تحميل الصورة وعرضها بـ BigPicture
      final response   = await http.get(Uri.parse(imageUrl));
      final tempDir    = await getTemporaryDirectory();
      final file       = File('${tempDir.path}/notif_img.jpg');
      await file.writeAsBytes(response.bodyBytes);

      androidDetails = AndroidNotificationDetails(
        'high_importance_channel',
        'إشعارات عامة',
        importance: Importance.high,
        priority:   Priority.high,
        playSound:  true,
        styleInformation: BigPictureStyleInformation(
          FilePathAndroidBitmap(file.path),
          contentTitle:   message.notification!.title,
          summaryText:    message.notification!.body,
          hideExpandedLargeIcon: true,
        ),
      );
    } else {
      androidDetails = const AndroidNotificationDetails(
        'high_importance_channel',
        'إشعارات عامة',
        importance: Importance.high,
        priority:   Priority.high,
        playSound:  true,
      );
    }

    await _local.show(
      message.hashCode,
      message.notification!.title,
      message.notification!.body,
      NotificationDetails(
        android: androidDetails,
        iOS: const DarwinNotificationDetails(sound: 'default'),
      ),
    );
  }
}
```

**الـ imports المطلوبة:**
```dart
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
```

**pubspec.yaml — أضف:**
```yaml
dependencies:
  http: ^1.x.x
  path_provider: ^2.x.x
```

---

## نموذج البيانات (Dart Model)

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

  factory AppNotification.fromJson(Map<String, dynamic> json) => AppNotification(
    id:        json['id'],
    title:     json['title'],
    message:   json['message'],
    imageUrl:  json['image_url'],
    sentAt:    json['sent_at'] != null ? DateTime.parse(json['sent_at']) : null,
    createdAt: DateTime.parse(json['created_at']),
  );
}
```

---

## ملخص Endpoints

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| `GET` | `/api/app/notifications` | ❌ | قائمة الإشعارات |
| `GET` | `/api/app/notifications/{id}` | ❌ | تفاصيل إشعار |
| `POST` | `/api/app/fcm-token` | ✅ Bearer | حفظ FCM token (اختياري) |

---

## FCM Payload الذي يرسله السيرفر (مع صورة)

```json
{
  "notification": {
    "title": "عنوان الإشعار",
    "body": "محتوى الإشعار",
    "image": "https://wat.diyala.org/media/notifications/img.jpg"
  },
  "android": {
    "priority": "high",
    "notification": {
      "sound":      "default",
      "channel_id": "high_importance_channel",
      "image":      "https://wat.diyala.org/media/notifications/img.jpg"
    }
  },
  "apns": {
    "headers": { "apns-priority": "10" },
    "payload": {
      "aps": {
        "sound":           "default",
        "badge":           1,
        "mutable-content": 1
      }
    },
    "fcm_options": {
      "image": "https://wat.diyala.org/media/notifications/img.jpg"
    }
  }
}
```

### iOS — Notification Service Extension (مطلوب لعرض الصورة)

بدون هذا الـ Extension الصورة **لن تظهر** على iOS في الـ killed/background state.

1. في Xcode → **File → New → Target → Notification Service Extension**
2. سمّه `NotificationService`
3. استبدل محتوى `NotificationService.swift` بـ:

```swift
import UserNotifications

class NotificationService: UNNotificationServiceExtension {
    var handler: ((UNNotificationContent) -> Void)?
    var content: UNMutableNotificationContent?

    override func didReceive(
        _ request: UNNotificationRequest,
        withContentHandler handler: @escaping (UNNotificationContent) -> Void
    ) {
        self.handler = handler
        self.content = request.content.mutableCopy() as? UNMutableNotificationContent

        guard
            let content  = self.content,
            let imageStr = content.userInfo["gcm.notification.image"] as? String
                        ?? (content.userInfo["image"] as? String),
            let imageUrl = URL(string: imageStr)
        else {
            handler(request.content); return
        }

        URLSession.shared.downloadTask(with: imageUrl) { url, _, _ in
            if let url = url,
               let attachment = try? UNNotificationAttachment(identifier: "image", url: url) {
                content.attachments = [attachment]
            }
            handler(content)
        }.resume()
    }

    override func serviceExtensionTimeWillExpire() {
        if let c = content { handler?(c) }
    }
}
```

---

## ملاحظات

| | |
|--|--|
| `channel_id` | يجب أن يكون `high_importance_channel` بالضبط |
| Android Background/Killed | الصورة تظهر تلقائياً عبر `android.notification.image` |
| iOS Background/Killed | يحتاج **Notification Service Extension** + `mutable-content:1` |
| Foreground | استخدم `BigPictureStyleInformation` كما في الكود أعلاه |
| `has_more: true` | توجد صفحات إضافية — استخدم `?page=2` للتنقل |
| تسجيل الخروج | السيرفر يحذف FCM token تلقائياً عند logout |

---

# 🏪 Store Status API — حالة المتجر (مفتوح / مغلق)

## Endpoint

**GET** `/api/app/store-status`  
🌐 لا يتطلب Auth — يُستدعى قبل كل إضافة للسلة

---

## Response — المتجر مفتوح ✅

```json
{
  "is_open": true,
  "message": "المتجر مفتوح"
}
```

## Response — المتجر مغلق ❌

```json
{
  "is_open": false,
  "message": "المتجر مغلق حالياً"
}
```

---

## أين يُضاف في Flutter؟

عند الضغط على زر **"أضف للسلة"** مباشرةً:

```dart
Future<void> addToCart(Product product) async {
  // 1. تحقق من حالة المتجر أولاً
  final res  = await http.get(
    Uri.parse('https://wat.diyala.org/api/app/store-status'),
    headers: {'Accept': 'application/json'},
  );
  final data = jsonDecode(res.body);

  // 2. إذا مغلق — أظهر رسالة وأوقف
  if (data['is_open'] == false) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('المتجر مغلق'),
        content: const Text('المتجر مغلق حالياً، يرجى المحاولة لاحقاً.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('حسناً')),
        ],
      ),
    );
    return; // ← لا تضيف للسلة
  }

  // 3. المتجر مفتوح — أكمل الإضافة
  cart.add(product);
}
```

---

## ملخص

| Method | URL | Auth | متى يُستدعى |
|--------|-----|------|------------|
| `GET` | `/api/app/store-status` | ❌ | عند الضغط على زر إضافة للسلة |
