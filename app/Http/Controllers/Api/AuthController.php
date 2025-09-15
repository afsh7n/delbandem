<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Controller
 * 
 * Handles user authentication via SMS verification
 */
class AuthController extends Controller
{
    private SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send verification code to phone number
     * 
     * Initiates the login process by sending a 4-digit verification code via SMS
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function preLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|regex:/^09\\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phoneNumber = $request->phoneNumber;
        $code = rand(1000, 9999);
        $expiresAt = now()->addMinutes(5);

        $user = User::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'phone_number' => $phoneNumber,
                'verification_code' => [
                    'code' => $code,
                    'expires' => $expiresAt,
                ],
            ]
        );

        if ($user->wasRecentlyCreated === false) {
            $user->update([
                'verification_code' => [
                    'code' => $code,
                    'expires' => $expiresAt,
                ],
            ]);
        }

        try {
            $this->smsService->sendSms($phoneNumber, (string)$code);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send SMS'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Verification code sent'], 201);
    }

    /**
     * Resend verification code
     * 
     * Resends a new 4-digit verification code to the user's phone number
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|regex:/^09\\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $phoneNumber = $request->phoneNumber;
        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $code = rand(1000, 9999);
        $expiresAt = now()->addMinutes(5);

        $user->update([
            'verification_code' => [
                'code' => $code,
                'expires' => $expiresAt,
            ],
        ]);

        try {
            $this->smsService->sendSms($phoneNumber, (string)$code);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send SMS'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Verification code resent'], 201);
    }

    /**
     * Verify code and login user
     * 
     * Verifies the SMS code and returns user data with authentication token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|regex:/^09\\d{9}$/',
            'code' => 'required|integer|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('phone_number', $request->phoneNumber)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $verificationCode = $user->verification_code;

        if (!$verificationCode ||
            $verificationCode['code'] != $request->code ||
            now()->gt($verificationCode['expires'])) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired verification code'], 401);
        }

        // Clear verification code
        $user->update(['verification_code' => null]);

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}