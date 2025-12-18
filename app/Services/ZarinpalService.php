<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Payment;
use Shetabit\Multipay\Invoice;

class ZarinpalService
{
    private bool $sandbox;

    public function __construct()
    {
        $this->sandbox = config('payment.default') === 'zarinpal-sandbox' ||
                        config('services.zarinpal.sandbox', false);
    }

    /**
     * Request payment for a plan
     */
    public function requestPayment(Plan $plan, int $userId): array
    {
        try {
            // Create subscription record
            $subscription = Subscription::create([
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'paid_price' => $plan->price,
            ]);

            // Convert Toman to Rial (multiply by 10)
            $amountInRial = $plan->price * 10;

            // Create invoice
            $invoice = (new Invoice)->amount($amountInRial)
                ->detail([
                    'description' => $plan->description ?? "خرید پلن {$plan->name}",
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'user_id' => $userId,
                ]);

            // Determine driver from config (sandbox or production)
            $driver = config('payment.default', 'zarinpal');

            // Purchase invoice
            $payment = Payment::via($driver)->purchase($invoice, function ($driver, $transactionId) use ($subscription) {
                // Save authority code
                $subscription->update([
                    'authority' => $transactionId
                ]);
            });

            // Get payment URL
            $paymentUrl = $payment->pay()->getAction();

            return [
                'success' => true,
                'message' => 'لینک پرداخت با موفقیت ایجاد شد',
                'data' => [
                    'payment_url' => $paymentUrl,
                    'authority' => $subscription->authority,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('ZarinPal Payment Request Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

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
    public function verifyPayment(string $authority, string $status): array
    {
        try {
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

            // Determine driver from config (sandbox or production)
            $driver = config('payment.default', 'zarinpal');

            // Verify payment - multipay automatically gets authority from request
            $receipt = Payment::via($driver)
                ->amount($subscription->paid_price * 10)
                ->transactionId($authority)
                ->verify();

            // Payment verified successfully
            $subscription->update([
                'ref_id' => $receipt->getReferenceId() ?? $receipt->getTraceNo() ?? null,
            ]);

            // Activate subscription
            $subscription->activate();

            return [
                'success' => true,
                'message' => 'پرداخت با موفقیت انجام شد',
                'ref_id' => $receipt->getReferenceId() ?? $receipt->getTraceNo() ?? null,
                'subscription' => $subscription->load('plan'),
            ];

        } catch (\Shetabit\Multipay\Exceptions\InvalidPaymentException $e) {
            Log::error('ZarinPal Payment Verification Error: ' . $e->getMessage(), [
                'authority' => $authority,
            ]);

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            return [
                'success' => false,
                'message' => 'تایید پرداخت با خطا مواجه شد: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('ZarinPal Payment Verification Error: ' . $e->getMessage(), [
                'authority' => $authority,
                'trace' => $e->getTraceAsString(),
            ]);

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

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
