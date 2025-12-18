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
            $apiUrl = config("payment.drivers.{$driver}.apiPurchaseUrl");
            
            // Test API connection first by making a direct request
            $testResponse = null;
            $testError = null;
            try {
                $testData = [
                    'MerchantID' => $merchantId,
                    'Amount' => $amountInRial,
                    'CallbackURL' => $callbackUrl,
                    'Description' => $plan->description ?? "خرید پلن {$plan->name}",
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                
                $testResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    $testError = "CURL Error: " . $curlError;
                } elseif ($httpCode !== 200) {
                    $testError = "HTTP Error: Status code {$httpCode}";
                } elseif ($testResponse) {
                    $decodedResponse = json_decode($testResponse, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $testError = "JSON Parse Error: " . json_last_error_msg();
                    } elseif (!isset($decodedResponse['Status']) && !isset($decodedResponse['code'])) {
                        $testError = "Invalid API Response: Missing Status/code field. Response: " . substr($testResponse, 0, 500);
                    }
                } else {
                    $testError = "Empty response from API";
                }
            } catch (\Exception $testEx) {
                $testError = "Test request exception: " . $testEx->getMessage();
            }

            // If test failed, include it in the error
            if ($testError) {
                throw new \Exception("خطا در اتصال به API زرین‌پال: {$testError}. Response: " . ($testResponse ? substr($testResponse, 0, 500) : 'null'));
            }
            
            try {
                $this->payment->via($driver)
                    ->callbackUrl($callbackUrl)
                    ->purchase($invoice, function ($driver, $transactionId) use ($subscription) {
                        // Save authority code
                        $subscription->update([
                            'authority' => $transactionId
                        ]);
                    });
            } catch (\Shetabit\Multipay\Exceptions\InvalidPaymentException $e) {
                throw new \Exception("خطا در درخواست پرداخت: " . $e->getMessage(), 0, $e);
            }

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
                    'trace' => $e->getPrevious()->getTraceAsString(),
                ] : null,
            ];

            // Provide more helpful error message
            $helpfulMessage = 'خطا در اتصال به درگاه پرداخت';
            if (str_contains($e->getMessage(), 'code') || str_contains($e->getMessage(), 'Undefined array key')) {
                $helpfulMessage = 'پاسخ نامعتبر از درگاه پرداخت. لطفاً Merchant ID و تنظیمات درگاه را بررسی کنید. احتمالاً Merchant ID معتبر نیست یا API زرین‌پال پاسخ درستی برنمی‌گرداند.';
            } elseif (str_contains($e->getMessage(), 'null')) {
                $helpfulMessage = 'پاسخ خالی از درگاه پرداخت. لطفاً اتصال اینترنت و تنظیمات درگاه را بررسی کنید.';
            }

            // Update subscription status to cancelled on error
            if (isset($subscription)) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            // Get all debug information for API response
            $currentDriver = config('payment.default', 'zarinpal');
            $driverConfig = config("payment.drivers.{$currentDriver}", []);

            // Prepare request data that was sent
            $requestData = [
                'MerchantID' => $merchantId ? (substr($merchantId, 0, 10) . '...' . substr($merchantId, -4)) : null,
                'Amount' => $amountInRial ?? null,
                'CallbackURL' => $callbackUrl ?? null,
                'Description' => $plan->description ?? "خرید پلن {$plan->name}",
            ];

            // Try to get API response if available
            $apiResponseInfo = null;
            try {
                $testData = [
                    'MerchantID' => $merchantId,
                    'Amount' => $amountInRial,
                    'CallbackURL' => $callbackUrl,
                    'Description' => $plan->description ?? "خرید پلن {$plan->name}",
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                
                $apiResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                $apiResponseInfo = [
                    'http_code' => $httpCode,
                    'curl_error' => $curlError ?: null,
                    'raw_response' => $apiResponse ? substr($apiResponse, 0, 1000) : null,
                    'response_decoded' => $apiResponse ? json_decode($apiResponse, true) : null,
                ];
            } catch (\Exception $apiTestEx) {
                $apiResponseInfo = [
                    'test_error' => $apiTestEx->getMessage(),
                ];
            }

            return [
                'success' => false,
                'message' => $helpfulMessage,
                'error' => $e->getMessage(),
                'error_details' => $errorDetails,
                'request_info' => [
                    'plan_id' => $plan->id ?? null,
                    'plan_name' => $plan->name ?? null,
                    'plan_price' => $plan->price ?? null,
                    'user_id' => $userId,
                    'amount_in_rial' => $amountInRial ?? null,
                    'amount_in_toman' => isset($amountInRial) ? ($amountInRial / 10) : null,
                    'subscription_created' => isset($subscription),
                    'subscription_id' => $subscription->id ?? null,
                    'subscription_status' => $subscription->status ?? null,
                ],
                'payment_config' => [
                    'driver' => $currentDriver,
                    'merchant_id_configured' => !empty($driverConfig['merchantId'] ?? null),
                    'merchant_id_length' => strlen($merchantId ?? ''),
                    'merchant_id_preview' => $merchantId ? (substr($merchantId, 0, 10) . '...' . substr($merchantId, -4)) : null,
                    'callback_url' => $driverConfig['callbackUrl'] ?? null,
                    'api_purchase_url' => $driverConfig['apiPurchaseUrl'] ?? null,
                    'api_payment_url' => $driverConfig['apiPaymentUrl'] ?? null,
                    'api_verification_url' => $driverConfig['apiVerificationUrl'] ?? null,
                    'mode' => $driverConfig['mode'] ?? null,
                    'server' => $driverConfig['server'] ?? null,
                    'currency' => $driverConfig['currency'] ?? null,
                ],
                'request_data' => $requestData,
                'api_response_test' => $apiResponseInfo ?? null,
                'suggestion' => 'لطفاً Merchant ID را در پنل زرین‌پال بررسی کنید و مطمئن شوید که معتبر است. برای sandbox از Merchant ID مخصوص sandbox استفاده کنید. همچنین مطمئن شوید که API زرین‌پال در دسترس است. اگر api_response_test موجود است، آن را بررسی کنید تا ببینید API چه پاسخی برمی‌گرداند.',
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
