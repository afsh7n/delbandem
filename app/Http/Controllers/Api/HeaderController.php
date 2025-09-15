<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Header;
use Illuminate\Http\JsonResponse;

/**
 * Header Controller
 * 
 * Handles header images and banner content
 */
class HeaderController extends Controller
{
    /**
     * Get header images
     * 
     * Returns header/banner images with full URLs
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $header = Header::first();

        if (!$header) {
            return response()->json(['success' => true, 'images' => []]);
        }

        $imagesWithUrls = collect($header->images)->map(function ($image) {
            return url('storage/' . $image);
        })->toArray();

        return response()->json(['success' => true, 'images' => $imagesWithUrls]);
    }
}