<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class ZarinpalService
{
    private string $merchantId;
    private string $callbackUrl;
    private string $apiRequestUrl;
    private string $apiVerifyUrl;
    private string $apiPaymentUrl;
    private bool $sandbox;

    public function __construct()
    {
        // Configuration with defaults
        $this->sandbox = env('ZARINPAL_SANDBOX', false);
        $this->merchantId = env('ZARINPAL_MERCHANT_ID', '2e3d6609-a5df-48df-99dc-3fdec26306fc');
        $this->callbackUrl = env('ZARINPAL_CALLBACK_URL', env('APP_URL') . '/payment/callback');
        $currency = env('ZARINPAL_CURRENCY', 'IRT'); // IRT (Toman) or IRR (Rial)
        
        // Set API URLs based on sandbox mode (using official ZarinPal API v4)
        if ($this->sandbox) {
            $this->apiRequestUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
            $this->apiVerifyUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
            $this->apiPaymentUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';
        } else {
            // Production API v4 URLs
            $this->apiRequestUrl = 'https://payment.zarinpal.com/pg/v4/payment/request.json';
            $this->apiVerifyUrl = 'https://payment.zarinpal.com/pg/v4/payment/verify.json';
            $this->apiPaymentUrl = 'https://payment.zarinpal.com/pg/StartPay/';
        }

        // Validate required fields
        if (empty($this->merchantId)) {
            throw new \Exception('ZARINPAL_MERCHANT_ID is not set. Please set it in .env file or use default value.');
        }

        if (empty($this->callbackUrl)) {
            throw new \Exception('ZARINPAL_CALLBACK_URL is not set. Please set it in .env file or ensure APP_URL is configured.');
        }
    }

    /**
     * Request payment for a plan
     */
    public function requestPayment(Plan $plan, int $userId): array
    {
        $subscription = null;
        
        try {
            // Create subscription record
            $subscription = Subscription::create([
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'paid_price' => $plan->price,
            ]);

            // Amount in Toman (API v4 accepts both Rial and Toman based on currency field)
            $amount = $plan->price; // Keep in Toman
            $currency = env('ZARINPAL_CURRENCY', 'IRT'); // IRT (Toman) or IRR (Rial)

            // Prepare request data according to ZarinPal API v4 documentation
            $requestData = [
                'merchant_id' => $this->merchantId,
                'amount' => $currency === 'IRT' ? $amount : ($amount * 10), // Convert to Rial if needed
                'callback_url' => $this->callbackUrl,
                'description' => $plan->description ?? "خرید پلن {$plan->name}",
                'currency' => $currency,
            ];

            // Optional: Add metadata if user info is available
            $user = \App\Models\User::find($userId);
            if ($user && $user->phone) {
                $requestData['metadata'] = [
                    'mobile' => $user->phone,
                ];
                if ($user->email) {
                    $requestData['metadata']['email'] = $user->email;
                }
                $requestData['metadata']['order_id'] = (string)$subscription->id;
            }

            // Make API request
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiRequestUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);

            // Handle CURL errors
            if ($curlError) {
                throw new \Exception("CURL Error: {$curlError}");
            }

            if ($httpCode !== 200) {
                throw new \Exception("HTTP Error: Status code {$httpCode}. Response: " . substr($response, 0, 500));
            }

            // Parse response
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON Parse Error: " . json_last_error_msg() . ". Response: " . substr($response, 0, 500));
            }

            // Check for errors in response
            if (isset($responseData['errors']) && !empty($responseData['errors'])) {
                $error = $responseData['errors'];
                $errorCode = $error['code'] ?? -9;
                $errorMessage = $error['message'] ?? 'خطای نامشخص';
                
                throw new \Exception("ZarinPal API Error (Code: {$errorCode}): {$errorMessage}");
            }

            // Check for data
            if (!isset($responseData['data'])) {
                throw new \Exception("Invalid API Response: Missing 'data' field. Response: " . substr($response, 0, 500));
            }

            $data = $responseData['data'];
            $code = $data['code'] ?? null;
            $authority = $data['authority'] ?? null;

            // Check response code
            if ($code !== 100) {
                $errorMessage = self::getErrorMessage($code);
                throw new \Exception("ZarinPal Payment Request Failed (Code: {$code}): {$errorMessage}");
            }

            if (empty($authority)) {
                throw new \Exception("Invalid API Response: Missing 'authority' field. Response: " . substr($response, 0, 500));
            }

            // Save authority to subscription
            $subscription->update([
                'authority' => $authority
            ]);

            // Build payment URL
            $paymentUrl = $this->apiPaymentUrl . $authority;

            return [
                'success' => true,
                'message' => 'لینک پرداخت با موفقیت ایجاد شد',
                'data' => [
                    'payment_url' => $paymentUrl,
                    'authority' => $authority,
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

            // Update subscription status to cancelled on error
            if ($subscription) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            // Get all debug information
            $requestData = [
                'merchant_id' => $this->merchantId ? (substr($this->merchantId, 0, 10) . '...' . substr($this->merchantId, -4)) : null,
                'amount' => $plan->price ?? null,
                'callback_url' => $this->callbackUrl,
                'description' => $plan->description ?? "خرید پلن {$plan->name}",
            ];

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت',
                'error' => $e->getMessage(),
                'error_details' => $errorDetails,
                'request_info' => [
                    'plan_id' => $plan->id ?? null,
                    'plan_name' => $plan->name ?? null,
                    'plan_price' => $plan->price ?? null,
                    'user_id' => $userId,
                    'amount' => $plan->price ?? null,
                    'subscription_created' => isset($subscription),
                    'subscription_id' => $subscription->id ?? null,
                    'subscription_status' => $subscription->status ?? null,
                ],
                'payment_config' => [
                    'merchant_id_configured' => !empty($this->merchantId),
                    'merchant_id_length' => strlen($this->merchantId ?? ''),
                    'merchant_id_preview' => $this->merchantId ? (substr($this->merchantId, 0, 10) . '...' . substr($this->merchantId, -4)) : null,
                    'callback_url' => $this->callbackUrl,
                    'api_request_url' => $this->apiRequestUrl,
                    'api_verify_url' => $this->apiVerifyUrl,
                    'api_payment_url' => $this->apiPaymentUrl,
                    'sandbox_mode' => $this->sandbox,
                ],
                'request_data' => $requestData,
                'suggestion' => 'لطفاً Merchant ID را در پنل زرین‌پال بررسی کنید و مطمئن شوید که معتبر است. برای sandbox از Merchant ID مخصوص sandbox استفاده کنید.',
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

            // Amount in Toman (convert to Rial if currency is IRR)
            $currency = env('ZARINPAL_CURRENCY', 'IRT');
            $amount = $currency === 'IRT' ? $subscription->paid_price : ($subscription->paid_price * 10);

            // Prepare verify request data according to ZarinPal API v4 documentation
            $requestData = [
                'merchant_id' => $this->merchantId,
                'amount' => $amount,
                'authority' => $authority,
            ];

            // Make API request
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiVerifyUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Handle CURL errors
            if ($curlError) {
                throw new \Exception("CURL Error: {$curlError}");
            }

            if ($httpCode !== 200) {
                throw new \Exception("HTTP Error: Status code {$httpCode}. Response: " . substr($response, 0, 500));
            }

            // Parse response
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON Parse Error: " . json_last_error_msg() . ". Response: " . substr($response, 0, 500));
            }

            // Check for errors in response
            if (isset($responseData['errors']) && !empty($responseData['errors'])) {
                $error = $responseData['errors'];
                $errorCode = $error['code'] ?? -9;
                $errorMessage = $error['message'] ?? 'خطای نامشخص';
                
                throw new \Exception("ZarinPal API Error (Code: {$errorCode}): {$errorMessage}");
            }

            // Check for data
            if (!isset($responseData['data'])) {
                throw new \Exception("Invalid API Response: Missing 'data' field. Response: " . substr($response, 0, 500));
            }

            $data = $responseData['data'];
            $code = $data['code'] ?? null;
            $refId = $data['ref_id'] ?? null;

            // Check response code
            if ($code !== 100 && $code !== 101) {
                $errorMessage = self::getErrorMessage($code);
                throw new \Exception("ZarinPal Payment Verification Failed (Code: {$code}): {$errorMessage}");
            }

            // Payment verified successfully
            $subscription->update([
                'ref_id' => $refId,
            ]);

            // Activate subscription
            $subscription->activate();

            return [
                'success' => true,
                'message' => 'پرداخت با موفقیت انجام شد',
                'ref_id' => $refId,
                'code' => $code,
                'code_message' => $code === 101 ? 'تراکنش قبلاً تایید شده است' : 'تراکنش با موفقیت تایید شد',
                'subscription' => $subscription->load('plan'),
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
     * Get error message based on ZarinPal error code
     */
    public static function getErrorMessage(int $code): string
    {
        $errors = [
            -9 => 'خطای اعتبارسنجی: مرچنت کد داخل تنظیمات وارد نشده باشد، آدرس بازگشت (callback_url) وارد نشده باشد، توضیحات (description) وارد نشده باشد یا از حد مجاز 500 کارکتر بیشتر باشد، مبلغ پرداختی کمتر یا بیشتر از حد مجاز، یا کد معرف (referrer_id) نامعتبر است',
            -10 => 'IP یا مرچنت کد پذیرنده صحیح نیست',
            -11 => 'مرچنت کد فعال نیست',
            -12 => 'تلاش بیش از حد در یک بازه زمانی کوتاه',
            -15 => 'ترمینال شما به حالت تعلیق در آمده است',
            -16 => 'سطح تایید پذیرنده پایین‌تر از سطح نقره‌ای است',
            -30 => 'اجازه دسترسی به تسویه اشتراکی را ندارید',
            -31 => 'حساب بانکی تسویه را به پنل خود اضافه کنید',
            -32 => 'مبلغ از کل مبلغ تراکنش بیشتر است',
            -33 => 'درصد های وارد شده صحیح نیست',
            -34 => 'مبلغ از کل تراکنش بیشتر است',
            -35 => 'تعداد افراد دریافت کننده تسویه اشتراکی از حد مجاز بیشتر است',
            -40 => 'اجازه دسترسی به متد مربوطه وجود ندارد',
            -50 => 'سطح پذیرنده پایین‌تر از سطح نقره‌ای است',
            -51 => 'استفاده از تسویه اشتراکی برای این پذیرنده امکان‌پذیر نیست',
            -52 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد',
            -53 => 'استفاده از تسویه اشتراکی برای این پذیرنده امکان‌پذیر نیست',
            -54 => 'درخواست مورد نظر آرشیو شده است',
            -101 => 'تراکنش با خطا مواجه شده است',
            100 => 'عملیات با موفقیت انجام شد',
            101 => 'عملیات پرداخت موفق بوده و قبلا تایید شده است',
        ];

        return $errors[$code] ?? "خطای نامشخص (کد: {$code})";
    }
}
