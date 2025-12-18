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
    private Payment $payment;

    public function __construct()
    {
        // Load payment config
        $paymentConfig = config('payment');
        
        // Instantiate Payment class with config
        $this->payment = new Payment($paymentConfig);
    }

    /**
     * Request payment for a plan
     */
    public function requestPayment(Plan $plan, int $userId): array
    {
        try {
            // Determine driver from config (sandbox or production)
            $driver = config('payment.default', 'zarinpal');

            // Validate configuration
            $merchantId = config("payment.drivers.{$driver}.merchantId");
            $callbackUrl = config("payment.drivers.{$driver}.callbackUrl");

            if (empty($merchantId)) {
                throw new \Exception("Merchant ID is not configured for driver: {$driver}. Please set ZARINPAL_MERCHANT_ID in your .env file.");
            }

            if (empty($callbackUrl)) {
                throw new \Exception("Callback URL is not configured for driver: {$driver}.");
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

            // Create invoice
            $invoice = (new Invoice)->amount($amountInRial)
                ->detail([
                    'description' => $plan->description ?? "خرید پلن {$plan->name}",
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'user_id' => $userId,
                ]);

            // Purchase invoice with callback URL
            $this->payment->via($driver)
                ->callbackUrl($callbackUrl)
                ->purchase($invoice, function ($driver, $transactionId) use ($subscription) {
                    // Save authority code
                    $subscription->update([
                        'authority' => $transactionId
                    ]);
                });

            // Get payment URL - construct from driver config and authority
            // Refresh subscription to get the saved authority
            $subscription->refresh();
            $apiPaymentUrl = config("payment.drivers.{$driver}.apiPaymentUrl");
            $paymentUrl = $apiPaymentUrl . $subscription->authority;

            return [
                'success' => true,
                'message' => 'لینک پرداخت با موفقیت ایجاد شد',
                'data' => [
                    'payment_url' => $paymentUrl,
                    'authority' => $subscription->authority,
                ],
            ];

        } catch (\Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious() ? [
                    'message' => $e->getPrevious()->getMessage(),
                    'file' => $e->getPrevious()->getFile(),
                    'line' => $e->getPrevious()->getLine(),
                ] : null,
            ];

            Log::error('ZarinPal Payment Request Error: ' . $e->getMessage(), [
                'error_details' => $errorDetails,
                'plan_id' => $plan->id ?? null,
                'user_id' => $userId,
                'driver' => config('payment.default'),
                'merchant_id_set' => !empty(config("payment.drivers." . config('payment.default') . ".merchantId")),
            ]);

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت',
                'error' => $e->getMessage(),
                'error_details' => $errorDetails,
                'debug_info' => [
                    'driver' => config('payment.default'),
                    'merchant_id_configured' => !empty(config("payment.drivers." . config('payment.default') . ".merchantId")),
                    'callback_url' => config("payment.drivers." . config('payment.default') . ".callbackUrl"),
                ],
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

            // Verify payment
            $receipt = $this->payment->via($driver)
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
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('ZarinPal Payment Verification Error: ' . $e->getMessage(), [
                'authority' => $authority,
                'error_details' => $errorDetails,
            ]);

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            return [
                'success' => false,
                'message' => 'تایید پرداخت با خطا مواجه شد: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'error_details' => $errorDetails,
            ];
        } catch (\Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('ZarinPal Payment Verification Error: ' . $e->getMessage(), [
                'authority' => $authority,
                'error_details' => $errorDetails,
            ]);

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            return [
                'success' => false,
                'message' => 'خطا در تایید پرداخت',
                'error' => $e->getMessage(),
                'error_details' => $errorDetails,
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
