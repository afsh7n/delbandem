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

        // Log callback for debugging
        \Log::info('Payment Callback', [
            'authority' => $authority,
            'status' => $status,
            'all_params' => $request->all(),
        ]);

        // Verify payment
        $result = $this->zarinpalService->verifyPayment($authority, $status);

        // Log verification result for debugging
        \Log::info('Payment Verification Result', [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'No message',
            'error' => $result['error'] ?? null,
            'code' => $result['code'] ?? null,
        ]);

        return view('payment.callback', [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'خطای نامشخص',
            'refId' => $result['ref_id'] ?? null,
            'subscription' => $result['subscription'] ?? null,
        ]);
    }
}

