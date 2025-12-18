# راهنمای راه‌اندازی Multipay

## تغییرات انجام شده

پکیج `zarinpal/zarinpal-php-sdk` حذف شد و به جای آن از پکیج `shetabit/multipay` استفاده می‌شود که از چندین درگاه پرداخت پشتیبانی می‌کند.

## نصب پکیج

در سرور یا محیط محلی خود، دستور زیر را اجرا کنید:

```bash
composer require shetabit/multipay
```

یا برای نصب همه پکیج‌ها:

```bash
composer install
```

سپس:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

## پیکربندی

### 1. فایل `.env`

مقادیر زیر را به فایل `.env` خود اضافه کنید:

```env
# ZarinPal Configuration
ZARINPAL_MERCHANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
ZARINPAL_CALLBACK_URL=${APP_URL}/payment/callback
ZARINPAL_SANDBOX=false
```

**نکته:** برای محیط تست، `ZARINPAL_SANDBOX` را برابر با `true` قرار دهید.

### 2. فایل کانفیگ

فایل `config/payment.php` ایجاد شده است که شامل تنظیمات زیر است:

- **default**: درگاه پیش‌فرض (zarinpal)
- **drivers**: تنظیمات درگاه‌های مختلف
  - `zarinpal`: درگاه تولید
  - `zarinpal-sandbox`: درگاه تست
- **map**: نگاشت درایورها به کلاس‌های مربوطه

### 3. مدیریت کانفیگ

می‌توانید تنظیمات را در فایل `config/payment.php` تغییر دهید:

```php
'drivers' => [
    'zarinpal' => [
        'merchantId' => env('ZARINPAL_MERCHANT_ID'),
        'callbackUrl' => env('ZARINPAL_CALLBACK_URL'),
        // سایر تنظیمات...
    ],
],
```

## استفاده از درگاه‌های دیگر

Multipay از درگاه‌های زیر پشتیبانی می‌کند:

- Zarinpal (زرین‌پال)
- Mellat (ملت)
- Saman (سامان)
- Payir (پی‌ایر)
- Sadad (سداد)
- Parsian (پارسیان)
- Pasargad (پاسارگاد)
- Pay (پی)
- IdPay (آیدی پی)
- PayPing (پی‌پینگ)
- و...

برای اضافه کردن درگاه جدید، در فایل `config/payment.php`:

1. درایور جدید را به `drivers` اضافه کنید
2. کلاس درایور را به `map` اضافه کنید
3. در `default` درگاه پیش‌فرض را تغییر دهید

## تغییرات در کد

### ZarinpalService

سرویس `ZarinpalService` بازنویسی شد تا از Multipay استفاده کند:

- استفاده از `Shetabit\Payment\Facade\Payment`
- استفاده از `Invoice` برای ایجاد فاکتور
- استفاده از `purchase()` و `verify()` برای پرداخت و تایید

### API Endpoints

همه endpoint‌ها بدون تغییر باقی مانده‌اند:

- `POST /api/payment/request` - درخواست پرداخت
- `GET /payment/callback` - کال‌بک پرداخت
- `GET /api/subscriptions` - لیست اشتراک‌ها
- `GET /api/subscriptions/active` - اشتراک فعال

## تست

برای تست در محیط Sandbox:

1. در `.env` مقدار `ZARINPAL_SANDBOX=true` را قرار دهید
2. از Merchant ID تست استفاده کنید
3. پرداخت را انجام دهید

## مستندات

برای اطلاعات بیشتر:
- [مستندات Multipay](https://github.com/shetabit/multipay)
- [مستندات ZarinPal](https://docs.zarinpal.com)

## پشتیبانی

در صورت بروز مشکل:
1. لاگ‌ها را در `storage/logs/laravel.log` بررسی کنید
2. مطمئن شوید که پکیج نصب شده است
3. کش Laravel را پاک کنید

