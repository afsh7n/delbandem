<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    private $appId;
    private $restApiKey;
    
    public function __construct()
    {
        $this->appId = '092bd07e-12f9-4bfe-b533-61384f1dd972';
        $this->restApiKey = 'os_v2_app_bev5a7qs7ff75njtme4e6hozoigqbjoqduyu3j43hgofffzuo2sqffmge26f7up4ce5buob7vfe2h7kiuvgtjtwgjcbbmwloxizgovq';
    }

    /**
     * ارسال نوتیفیکیشن به تمام کاربران
     */
    public function sendToAll($title, $message, $data = [], $image = null, $url = null)
    {
        try {
            $payload = [
                'app_id' => $this->appId,
                'contents' => ['en' => $message],
                'headings' => ['en' => $title],
                'included_segments' => ['All'],
                'data' => $data,
            ];

            // اضافه کردن تصویر در صورت وجود
            if ($image) {
                $payload['big_picture'] = $image;
                $payload['content_available'] = true;
            }

            // اضافه کردن لینک در صورت وجود
            if ($url) {
                $payload['url'] = $url;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->restApiKey,
            ])->post('https://onesignal.com/api/v1/notifications', $payload);

            Log::info('OneSignal Response: ', ['response' => $response->json()]);
            
            return $response->json();
        } catch (Exception $e) {
            Log::error('OneSignal Error: ' . $e->getMessage());
            throw $e;
        }
    }
}

