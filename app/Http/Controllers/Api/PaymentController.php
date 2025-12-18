<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ZarinpalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private ZarinpalService $zarinpalService;

    public function __construct(ZarinpalService $zarinpalService)
    {
        $this->zarinpalService = $zarinpalService;
    }

    /**
     * Get all available plans
     */
    public function getPlans(Request $request)
    {
        $plans = Plan::active()->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Get a specific plan
     */
    public function getPlan(Request $request, $id)
    {
        $plan = Plan::active()->find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'پلن یافت نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    /**
     * Request payment for a plan
     */
    public function requestPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = Plan::active()->find($request->plan_id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'پلن مورد نظر یافت نشد یا غیرفعال است',
            ], 404);
        }

        $user = Auth::user();

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'شما در حال حاضر یک اشتراک فعال دارید',
            ], 400);
        }

        // Request payment
        $result = $this->zarinpalService->requestPayment($plan, $user->id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'لینک پرداخت با موفقیت ایجاد شد',
                'data' => $result['data'] ?? [],
            ]);
        }

        // Return full error details
        $response = [
            'success' => false,
            'message' => $result['message'] ?? 'خطا در اتصال به درگاه پرداخت',
        ];

        // Add error details if available
        if (isset($result['error'])) {
            $response['error'] = $result['error'];
        }

        // Add error details if available
        if (isset($result['error_details'])) {
            $response['error_details'] = $result['error_details'];
        }

        // Add debug info if available
        if (isset($result['debug_info'])) {
            $response['debug_info'] = $result['debug_info'];
        }

        return response()->json($response, 500);
    }

    /**
     * Get user's subscriptions history
     */
    public function getSubscriptions(Request $request)
    {
        $user = Auth::user();
        
        $subscriptions = Subscription::where('user_id', $user->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Get current active subscription
     */
    public function getActiveSubscription(Request $request)
    {
        $user = Auth::user();
        
        $subscription = $user->activeSubscription()->with('plan')->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'اشتراک فعالی یافت نشد',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'days_remaining' => max(0, now()->diffInDays($subscription->end_date, false)),
            ],
        ]);
    }
}

