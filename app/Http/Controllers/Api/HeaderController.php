<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Header;
use Illuminate\Http\JsonResponse;

class HeaderController extends Controller
{
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