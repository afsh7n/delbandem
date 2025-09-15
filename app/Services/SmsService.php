<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $apiKey;
    private string $securityCode;
    private string $tokenUrl;
    private string $smsUrl;

    public function __construct()
    {
        $this->apiKey = config('sms.api_key');
        $this->securityCode = config('sms.security_code');
        $this->tokenUrl = config('sms.token_url');
        $this->smsUrl = config('sms.sms_url');
    }

    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            $token = $this->getToken();
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-sms-ir-secure-token' => $token,
            ])->post($this->smsUrl, [
                'Code' => $message,
                'MobileNumber' => $phoneNumber,
            ]);

            if ($response->failed()) {
                throw new RequestException($response);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getToken(): string
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->tokenUrl, [
                'UserApiKey' => $this->apiKey,
                'SecretKey' => $this->securityCode,
            ]);

            if ($response->failed()) {
                throw new RequestException($response);
            }

            $data = $response->json();
            return $data['TokenKey'];
        } catch (\Exception $e) {
            Log::error('SMS token retrieval failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
