<?php

namespace App\Http\Controllers;

use App\Services\ZarinpalService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    private ZarinpalService $zarinpalService;

    public function __construct(ZarinpalService $zarinpalService)
    {
        $this->zarinpalService = $zarinpalService;
    }

    /**
     * Handle payment callback from ZarinPal
     */
    public function callback(Request $request)
    {
        $authority = $request->get('Authority');
        $status = $request->get('Status');

        // Verify payment
        $result = $this->zarinpalService->verifyPayment($authority, $status);

        return view('payment.callback', [
            'success' => $result['success'],
            'message' => $result['message'],
            'refId' => $result['ref_id'] ?? null,
            'subscription' => $result['subscription'] ?? null,
        ]);
    }
}

