<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Setting Controller
 * 
 * Handles setting-related operations for retrieving application settings
 */
class SettingController extends Controller
{
    /**
     * Get all settings
     * 
     * Returns a list of all application settings
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        });

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    /**
     * Get single setting by key
     * 
     * Returns a specific setting by its key
     * 
     * @param string $key Setting key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'setting' => [
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'description' => $setting->description,
            ]
        ]);
    }

    /**
     * Get multiple settings by keys
     * 
     * Returns multiple settings based on provided keys
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getByKeys(Request $request): JsonResponse
    {
        $keys = $request->input('keys', []);
        
        if (empty($keys) || !is_array($keys)) {
            return response()->json([
                'success' => false,
                'message' => 'Keys array is required'
            ], 422);
        }

        $settings = Setting::whereIn('key', $keys)->get()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        });

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }
}