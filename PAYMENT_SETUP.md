# راهنمای راه‌اندازی سیستم پرداخت و اشتراک

## نصب و پیکربندی

### 1. نصب پکیج‌ها

ابتدا پکیج‌های لازم را نصب کنید:

```bash
composer install
```

### 2. تنظیمات محیطی (Environment)

فایل `.env` خود را باز کنید و تنظیمات زیر را اضافه کنید:

```env
# ZarinPal Configuration
ZARINPAL_MERCHANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
ZARINPAL_CALLBACK_URL=${APP_URL}/payment/callback
ZARINPAL_SANDBOX=false
```

**نکته:** برای محیط تست، `ZARINPAL_SANDBOX` را برابر با `true` قرار دهید.

### 3. اجرای مایگریشن‌ها

برای ایجاد جداول `plans` و `subscriptions`:

```bash
php artisan migrate
```

## ساختار دیتابیس

### جدول Plans
- `id`: شناسه یکتا
- `name`: نام پلن
- `duration_days`: مدت زمان به روز
- `price`: قیمت به تومان
- `description`: توضیحات
- `is_active`: وضعیت فعال بودن
- `created_at`, `updated_at`: تاریخ‌های ایجاد و بروزرسانی

### جدول Subscriptions
- `id`: شناسه یکتا
- `user_id`: شناسه کاربر
- `plan_id`: شناسه پلن
- `status`: وضعیت (pending, active, expired, cancelled)
- `start_date`: تاریخ شروع
- `end_date`: تاریخ پایان
- `paid_price`: مبلغ پرداختی به تومان
- `authority`: کد Authority از زرین‌پال
- `ref_id`: کد پیگیری تراکنش
- `created_at`, `updated_at`: تاریخ‌های ایجاد و بروزرسانی

## API Endpoints

### عمومی (Public)

#### لیست پلن‌ها
```
GET /api/plans
```

#### جزئیات یک پلن
```
GET /api/plans/{id}
```

### احراز هویت شده (Protected - نیاز به توکن)

#### درخواست پرداخت
```
POST /api/payment/request
Headers: Authorization: Bearer {token}
Body: {
  "plan_id": 1
}

Response: {
  "success": true,
  "message": "لینک پرداخت با موفقیت ایجاد شد",
  "data": {
    "payment_url": "https://www.zarinpal.com/pg/StartPay/...",
    "authority": "A000000000000000000000000000xxxxx"
  }
}
```

#### لیست اشتراک‌های کاربر
```
GET /api/subscriptions
Headers: Authorization: Bearer {token}
```

#### اشتراک فعال کاربر
```
GET /api/subscriptions/active
Headers: Authorization: Bearer {token}

Response: {
  "success": true,
  "data": {
    "subscription": {...},
    "days_remaining": 25
  }
}
```

#### پروفایل کاربر (با اطلاعات اشتراک)
```
GET /api/users/me
Headers: Authorization: Bearer {token}

Response: {
  "success": true,
  "user": {...},
  "subscription": {
    "has_subscription": true,
    "plan_name": "پلن یک ماهه",
    "start_date": "2025-12-16T10:00:00+00:00",
    "end_date": "2026-01-16T10:00:00+00:00",
    "days_remaining": 30
  }
}
```

### Callback

```
GET /payment/callback?Authority={authority}&Status={status}
```

این روت توسط زرین‌پال فراخوانی می‌شود و یک صفحه HTML زیبا نمایش می‌دهد که:
- وضعیت پرداخت را نشان می‌دهد
- جزئیات اشتراک را نمایش می‌دهد
- دکمه بازگشت به اپلیکیشن دارد
- به صورت خودکار بعد از 3 ثانیه کاربر را به اپلیکیشن هدایت می‌کند

## پنل ادمین (Filament)

### مدیریت پلن‌ها

در پنل ادمین می‌توانید:
- پلن‌های جدید ایجاد کنید
- پلن‌های موجود را ویرایش کنید
- پلن‌ها را فعال یا غیرفعال کنید
- لیست تمام پلن‌ها را مشاهده کنید

**مسیر:** `/admin/plans`

### مدیریت اشتراک‌ها

در پنل ادمین می‌توانید:
- تمام اشتراک‌ها را مشاهده کنید
- فیلتر بر اساس وضعیت، پلن
- اشتراک‌ها را ویرایش کنید (تغییر وضعیت، تاریخ‌ها)
- اشتراک‌های فعال را مشاهده کنید

**مسیر:** `/admin/subscriptions`

## فلوی پرداخت

1. **درخواست پرداخت:**
   - کاربر از اپلیکیشن `plan_id` را ارسال می‌کند
   - سرور یک `Subscription` با وضعیت `pending` ایجاد می‌کند
   - درخواست به زرین‌پال ارسال می‌شود
   - لینک پرداخت به کاربر برگردانده می‌شود

2. **پرداخت:**
   - کاربر به درگاه زرین‌پال هدایت می‌شود
   - پرداخت را انجام می‌دهد

3. **Callback:**
   - زرین‌پال کاربر را به `/payment/callback` هدایت می‌کند
   - سرور پرداخت را تایید می‌کند
   - در صورت موفقیت:
     - وضعیت اشتراک به `active` تغییر می‌کند
     - `start_date` و `end_date` ست می‌شوند
     - `ref_id` ذخیره می‌شود
   - صفحه نتیجه نمایش داده می‌شود
   - کاربر به اپلیکیشن برگردانده می‌شود

## Deep Link برای اپلیکیشن

برای بازگشت به اپلیکیشن از این فرمت استفاده می‌شود:

```
myapp://payment/success  # پرداخت موفق
myapp://payment/failed   # پرداخت ناموفق
```

**نکته:** در فایل `resources/views/payment/callback.blade.php` می‌توانید `myapp://` را با Deep Link اپلیکیشن خود جایگزین کنید.

## بررسی اشتراک فعال

### در کد PHP:

```php
$user = Auth::user();

// چک کردن اشتراک فعال
if ($user->hasActiveSubscription()) {
    // کاربر اشتراک فعال دارد
}

// دریافت اشتراک فعال
$subscription = $user->activeSubscription;

// دریافت اطلاعات اشتراک برای API
$info = $user->getSubscriptionInfo();
```

### در API:

```php
// اطلاعات اشتراک به صورت خودکار در login و profile برگردانده می‌شود
```

## تست در محیط Sandbox

برای تست در محیط Sandbox زرین‌پال:

1. در `.env` مقدار `ZARINPAL_SANDBOX=true` را قرار دهید
2. از Merchant ID تست استفاده کنید
3. برای پرداخت موفق از کارت تست استفاده کنید

## نکات امنیتی

1. همیشه `ZARINPAL_MERCHANT_ID` را در `.env` نگه دارید و آن را commit نکنید
2. در production حتما `ZARINPAL_SANDBOX=false` باشد
3. تایید پرداخت از سمت سرور انجام می‌شود (نه کلاینت)
4. همه endpoint‌های پرداخت نیاز به احراز هویت دارند

## خطایابی

### لاگ‌ها:

تمام خطاهای مربوط به زرین‌پال در `storage/logs/laravel.log` ثبت می‌شوند.

### مشکلات رایج:

1. **خطای 422 در درخواست پرداخت:**
   - مطمئن شوید `plan_id` صحیح است
   - چک کنید پلن فعال (`is_active = true`) باشد

2. **خطای اتصال به زرین‌پال:**
   - `ZARINPAL_MERCHANT_ID` را چک کنید
   - اتصال اینترنت سرور را بررسی کنید

3. **کاربر قبلا اشتراک فعال دارد:**
   - ابتدا باید اشتراک قبلی منقضی شود یا لغو شود

## Command برای بروزرسانی اشتراک‌های منقضی شده

می‌توانید یک Command برای بروزرسانی خودکار اشتراک‌های منقضی شده ایجاد کنید:

```php
// در Scheduler (app/Console/Kernel.php)
$schedule->call(function () {
    Subscription::where('status', Subscription::STATUS_ACTIVE)
        ->where('end_date', '<=', now())
        ->update(['status' => Subscription::STATUS_EXPIRED]);
})->daily();
```

## پشتیبانی

در صورت بروز مشکل، ابتدا:
1. لاگ‌ها را بررسی کنید
2. مستندات زرین‌پال را مطالعه کنید: https://docs.zarinpal.com
3. مطمئن شوید تمام مایگریشن‌ها اجرا شده‌اند

