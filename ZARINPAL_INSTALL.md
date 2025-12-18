# راهنمای نصب پکیج زرین‌پال

## مشکل
خطای `Class "Zarinpal\Zarinpal" not found` به این معنی است که پکیج زرین‌پال نصب نشده است.

## راه حل

### 1. نصب پکیج
در سرور یا محیط محلی خود، دستور زیر را اجرا کنید:

```bash
composer install
```

یا اگر فقط می‌خواهید پکیج زرین‌پال را نصب کنید:

```bash
composer require zarinpal/zarinpal-php-sdk
```

### 2. بررسی نصب
پس از نصب، مطمئن شوید که پکیج در `vendor/zarinpal/zarinpal-php-sdk` وجود دارد.

### 3. پاک‌سازی کش
پس از نصب، کش Laravel را پاک کنید:

```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### 4. بررسی namespace
اگر پس از نصب همچنان خطا دارید، ممکن است namespace پکیج متفاوت باشد. در این صورت:

1. فایل `vendor/zarinpal/zarinpal-php-sdk/src/Zarinpal.php` را بررسی کنید
2. namespace کلاس را در فایل `app/Services/ZarinpalService.php` اصلاح کنید

### 5. استفاده از پکیج جایگزین (در صورت نیاز)
اگر پکیج `zarinpal/zarinpal-php-sdk` کار نکرد، می‌توانید از پکیج‌های زیر استفاده کنید:

#### گزینه 1: pishran/zarinpal
```bash
composer require pishran/zarinpal
```

سپس در `app/Services/ZarinpalService.php`:
```php
use Pishran\Zarinpal\Zarinpal;
```

#### گزینه 2: rahmatwaisi/zarinpal
```bash
composer require rahmatwaisi/zarinpal
```

سپس در `app/Services/ZarinpalService.php`:
```php
use RahmatWaisi\Zarinpal\Facade\Zarinpal;
```

## نکته مهم
پس از نصب پکیج، حتماً `composer dump-autoload` را اجرا کنید تا autoloader به‌روزرسانی شود.


