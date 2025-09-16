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
        $headers = Header::query()->get();

        if (!count($headers)) {
            return response()->json(['success' => true, 'images' => []]);
        }

        $imagesWithUrls = [];
        foreach ($headers as $header) {
            $imagesWithUrls = [...$imagesWithUrls, ...collect($header->images)->map(function ($image) {
                return url('storage/' . $image);
            })->toArray()];
        }

        return response()->json(['success' => true, 'images' => $imagesWithUrls]);
    }
}
