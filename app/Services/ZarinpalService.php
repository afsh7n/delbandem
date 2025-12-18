<?php

namespace App\Services;

use Zarinpal\Zarinpal as ZarinpalClient;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class ZarinpalService
{
    private ?ZarinpalClient $zarinpal = null;
    private string $merchantId;
    private string $callbackUrl;
    private bool $sandbox;

    public function __construct()
    {
        $this->merchantId = config('services.zarinpal.merchant_id');
        $this->callbackUrl = config('services.zarinpal.callback_url');
        $this->sandbox = config('services.zarinpal.sandbox', false);

        if (class_exists(ZarinpalClient::class)) {
            $this->zarinpal = new ZarinpalClient($this->merchantId, $this->sandbox);
        }
    }

    /**
     * Request payment for a plan
     */
    public function requestPayment(Plan $plan, int $userId): array
    {
        try {
            if (!$this->zarinpal) {
                return [
                    'success' => false,
                    'message' => 'پکیج زرین‌پال نصب نشده است. لطفا composer install را اجرا کنید.',
                ];
            }

            // Create subscription record
            $subscription = Subscription::create([
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'paid_price' => $plan->price,
            ]);

            // Convert Toman to Rial (multiply by 10)
            $amountInRial = $plan->price * 10;

            // Request payment from ZarinPal
            $result = $this->zarinpal->request(
                $amountInRial,
                $plan->description ?? "خرید پلن {$plan->name}",
                '',
                '',
                [
                    'subscription_id' => $subscription->id,
                ]
            );

            if ($result['Status'] == 100) {
                // Save authority code
                $subscription->update([
                    'authority' => $result['Authority']
                ]);

                // Return payment URL
                return [
                    'success' => true,
                    'payment_url' => $this->zarinpal->getRedirectUrl($result['Authority']),
                    'authority' => $result['Authority'],
                    'subscription_id' => $subscription->id,
                ];
            }

            // Payment request failed
            $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد درخواست پرداخت',
                'error' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('ZarinPal Payment Request Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment callback
     */
    public function verifyPayment(string $authority, int $status): array
    {
        try {
            if (!$this->zarinpal) {
                return [
                    'success' => false,
                    'message' => 'پکیج زرین‌پال نصب نشده است. لطفا composer install را اجرا کنید.',
                ];
            }

            // Find subscription by authority
            $subscription = Subscription::where('authority', $authority)->first();

            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'اشتراک یافت نشد',
                ];
            }

            // Check if payment was cancelled by user
            if ($status != 'OK') {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
                
                return [
                    'success' => false,
                    'message' => 'پرداخت توسط کاربر لغو شد',
                    'subscription' => $subscription,
                ];
            }

            // Verify payment with ZarinPal
            $amountInRial = $subscription->paid_price * 10;
            
            $result = $this->zarinpal->verify(
                $amountInRial,
                $authority
            );

            if ($result['Status'] == 100 || $result['Status'] == 101) {
                // Payment verified successfully
                $subscription->update([
                    'ref_id' => $result['RefID'] ?? null,
                ]);

                // Activate subscription
                $subscription->activate();

                return [
                    'success' => true,
                    'message' => 'پرداخت با موفقیت انجام شد',
                    'ref_id' => $result['RefID'] ?? null,
                    'subscription' => $subscription->load('plan'),
                ];
            }

            // Payment verification failed
            $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

            return [
                'success' => false,
                'message' => 'تایید پرداخت با خطا مواجه شد',
                'error' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('ZarinPal Payment Verification Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در تایید پرداخت',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment status text
     */
    public static function getStatusText(int $status): string
    {
        $statuses = [
            -1 => 'اطلاعات ارسال شده ناقص است',
            -2 => 'IP و یا مرچنت کد پذیرنده صحیح نیست',
            -3 => 'با توجه به محدودیت‌های شاپرک امکان پرداخت با رقم درخواست شده میسر نمی‌باشد',
            -4 => 'سطح تایید پذیرنده پایین‌تر از سطح نقره‌ای است',
            -11 => 'درخواست مورد نظر یافت نشد',
            -12 => 'امکان ویرایش درخواست میسر نمی‌باشد',
            -21 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد',
            -22 => 'تراکنش ناموفق بوده است',
            -33 => 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد',
            -34 => 'سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است',
            -40 => 'اجازه دسترسی به متد مربوطه وجود ندارد',
            -41 => 'اطلاعات ارسال شده مربوط به AdditionalData غیرمعتبر می‌باشد',
            -42 => 'مدت زمان معتبر طول عمر شناسه پرداخت باید بین 30 دقیقه تا 45 روز می‌باشد',
            -54 => 'درخواست مورد نظر آرشیو شده است',
            100 => 'عملیات با موفقیت انجام شد',
            101 => 'عملیات پرداخت موفق بوده و قبلا تایید شده است',
        ];

        return $statuses[$status] ?? 'وضعیت نامشخص';
    }
}

