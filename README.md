# Laravel Story Management System

پروژه مدیریت داستان‌ها با پنل ادمین فلیمنت و API کامل

## ویژگی‌ها

- **پنل ادمین فلیمنت**: مدیریت کاربران، دسته‌بندی‌ها، داستان‌ها، اعلان‌ها و هدرها
- **API کامل**: شامل احراز هویت، مدیریت داستان‌ها، علاقه‌مندی‌ها و امتیازدهی
- **احراز هویت با SMS**: سیستم ورود با کد تأیید پیامکی
- **آپلود فایل**: پشتیبانی از آپلود تصاویر و فایل‌های صوتی
- **سیستم امتیازدهی**: امکان امتیازدهی به داستان‌ها توسط کاربران

## نصب و راه‌اندازی

### 1. کلون کردن پروژه
```bash
git clone <repository-url>
cd delbandem
```

### 2. نصب وابستگی‌ها
```bash
composer install
```

### 3. تنظیمات محیطی
فایل `.env` را ایجاد کرده و تنظیمات زیر را اضافه کنید:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delbandem
DB_USERNAME=root
DB_PASSWORD=

# SMS Configuration
SMS_API_KEY=your_sms_api_key
SMS_SECURITY_CODE=your_sms_security_code
SMS_TOKEN_URL=your_token_url
SMS_URL=your_sms_url
```

### 4. ایجاد کلید برنامه
```bash
php artisan key:generate
```

### 5. اجرای Migration ها
```bash
php artisan migrate
```

### 6. ایجاد Symbolic Link برای Storage
```bash
php artisan storage:link
```

### 7. ایجاد کاربر ادمین
```bash
php artisan make:filament-user
```

## API Endpoints

### احراز هویت (Auth)
- `POST /api/auth/pre-login` - ارسال کد تأیید
- `POST /api/auth/pre-login/resend-code` - ارسال مجدد کد تأیید
- `POST /api/auth/login` - ورود با کد تأیید

### داستان‌ها (Stories) - عمومی
- `GET /api/stories` - لیست تمام داستان‌ها
- `GET /api/stories/{id}` - نمایش یک داستان
- `GET /api/stories/category/{categoryId}` - داستان‌های یک دسته‌بندی
- `GET /api/stories/categories` - لیست دسته‌بندی‌ها
- `GET /api/stories/new-stories` - جدیدترین داستان‌ها
- `GET /api/stories/best-stories` - بهترین داستان‌ها

### کاربران (Users) - نیازمند احراز هویت
- `GET /api/users/me` - اطلاعات کاربر جاری
- `PUT /api/users/add-to-favorites` - اضافه کردن به علاقه‌مندی‌ها
- `PUT /api/users/rate-story` - امتیازدهی به داستان
- `GET /api/users/favorites` - لیست علاقه‌مندی‌های کاربر

### اعلان‌ها (Notifications) - عمومی
- `GET /api/notifications` - لیست اعلان‌ها

### هدر (Header) - عمومی
- `GET /api/header` - تصاویر هدر

## پنل ادمین

پنل ادمین در آدرس `/admin` در دسترس است.

### منابع موجود در پنل ادمین:
- **Users**: مدیریت کاربران
- **Categories**: مدیریت دسته‌بندی‌ها
- **Stories**: مدیریت داستان‌ها (شامل آپلود تصویر و فایل صوتی)
- **Notifications**: مدیریت اعلان‌ها
- **Headers**: مدیریت تصاویر هدر

## ساختار دیتابیس

### جداول اصلی:
- `users` - اطلاعات کاربران
- `categories` - دسته‌بندی‌ها
- `stories` - داستان‌ها
- `notifications` - اعلان‌ها
- `headers` - تصاویر هدر
- `user_favorites` - جدول pivot برای علاقه‌مندی‌ها

## تنظیمات SMS

برای فعال‌سازی سرویس SMS، باید کلیدهای مربوطه را در فایل `.env` تنظیم کنید:

```env
SMS_API_KEY=your_api_key_here
SMS_SECURITY_CODE=your_security_code_here
SMS_TOKEN_URL=https://api.sms.ir/v1/Token
SMS_URL=https://api.sms.ir/v1/send/verify
```

## فایل‌های آپلود شده

فایل‌ها در مسیرهای زیر ذخیره می‌شوند:
- تصاویر کاربران: `storage/app/public/users/`
- تصاویر داستان‌ها: `storage/app/public/stories/images/`
- فایل‌های صوتی داستان‌ها: `storage/app/public/stories/voices/`
- تصاویر هدر: `storage/app/public/headers/`

## کامپایل کردن Assets

```bash
npm run build
```

## اجرای پروژه

```bash
php artisan serve
```

پروژه در آدرس `http://localhost:8000` در دسترس خواهد بود.

## ویژگی‌های پنل ادمین

- **داشبورد سفارشی**: نمایش آمار کلی سیستم در 4 باکس بزرگ
- **تم بنفش ملایم**: طراحی زیبا با رنگ‌بندی بنفش
- **منوی فارسی**: تمام منوها و عناوین به زبان فارسی
- **آیکون‌های مناسب**: آیکون مخصوص برای هر بخش
- **پشتیبانی کامل از RTL**: طراحی راست‌چین برای زبان فارسی
- **فونت وزیر**: استفاده از فونت زیبای وزیر از گوگل فونت
- **تنظیمات فارسی**: timezone تهران و locale فارسی

### آمار داشبورد شامل:
- تعداد دسته‌بندی‌ها
- تعداد داستان‌ها  
- تعداد اعلان‌ها
- تعداد هدرها

## لایسنس

این پروژه تحت لایسنس MIT منتشر شده است.