<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتیجه پرداخت</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .icon.success {
            background: #d4edda;
            color: #28a745;
        }

        .icon.error {
            background: #f8d7da;
            color: #dc3545;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }

        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: right;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }

        .detail-value {
            color: #6c757d;
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-right: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 20px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .btn-secondary {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($success)
            <div class="icon success">
                ✓
            </div>
            <h1>پرداخت موفق</h1>
            <p class="message">{{ $message }}</p>

            @if($subscription)
                <div class="details">
                    <div class="detail-row">
                        <span class="detail-label">نام پلن:</span>
                        <span class="detail-value">{{ $subscription->plan->name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">مبلغ پرداختی:</span>
                        <span class="detail-value">{{ number_format($subscription->paid_price) }} تومان</span>
                    </div>
                    @if($refId)
                        <div class="detail-row">
                            <span class="detail-label">کد پیگیری:</span>
                            <span class="detail-value">{{ $refId }}</span>
                        </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">تاریخ شروع:</span>
                        <span class="detail-value">{{ $subscription->start_date?->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">تاریخ پایان:</span>
                        <span class="detail-value">{{ $subscription->end_date?->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            @endif
        @else
            <div class="icon error">
                ✕
            </div>
            <h1>پرداخت ناموفق</h1>
            <p class="message">{{ $message }}</p>
        @endif

        <div class="buttons">
            <a href="myapp://payment/{{ $success ? 'success' : 'failed' }}" class="btn btn-primary">
                بازگشت به اپلیکیشن
            </a>
            <a href="{{ url('/') }}" class="btn btn-secondary">
                صفحه اصلی
            </a>
        </div>
    </div>

    <script>
        // Auto redirect to app after 3 seconds
        setTimeout(() => {
            window.location.href = 'myapp://payment/{{ $success ? 'success' : 'failed' }}';
        }, 3000);
    </script>
</body>
</html>

