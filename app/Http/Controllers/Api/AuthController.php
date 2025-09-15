<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function preLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|regex:/^09\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
            return response()->json(['message' => 'Failed to send SMS'], 500);
        }

        return response()->json(['message' => 'Verification code sent'], 201);
    }

    public function resendCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|regex:/^09\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $phoneNumber = $request->phoneNumber;
        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
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
            return response()->json(['message' => 'Failed to send SMS'], 500);
        }

        return response()->json(['message' => 'Verification code resent'], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|regex:/^09\d{9}$/',
            'code' => 'required|integer|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('phone_number', $request->phoneNumber)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $verificationCode = $user->verification_code;

        if (!$verificationCode || 
            $verificationCode['code'] != $request->code || 
            now()->gt($verificationCode['expires'])) {
            return response()->json(['message' => 'Invalid or expired verification code'], 401);
        }

        // Clear verification code
        $user->update(['verification_code' => null]);

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}
