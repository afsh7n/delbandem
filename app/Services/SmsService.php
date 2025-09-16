<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendVerificationCode($mobile,$code)
    {
        $token =  '8hNn5JQqeykL1yyzE5GnXCUZCRdfTlKcqkubve0gyMnpOdB7KYZqDvEtVEFJ6cDv';
        return Http::withHeaders(['Content-Type' => 'application/json' , 'X-API-KEY'=>$token])->post('https://api.sms.ir/v1/send/verify', [
            'Parameters'=>[
                [
                    'name' => 'Code',
                    'value' => $code
                ]
            ],
            'Mobile' => $mobile,
            'TemplateId' => '991916'
        ]);
    }
}
