<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function index(): JsonResponse
    {
        $stories = Story::with('category')->get();
        return response()->json($stories);
    }

    public function show(string $id): JsonResponse
    {
        $story = Story::with('category')->findOrFail($id);
        return response()->json($story);
    }

    public function getByCategory(string $categoryId): JsonResponse
    {
        $stories = Story::with('category')
            ->where('category_id', $categoryId)
            ->get();
        
        return response()->json($stories);
    }

    public function getCategories(): JsonResponse
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function getNewStories(): JsonResponse
    {
        $stories = Story::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json($stories);
    }

    public function getBestStories(): JsonResponse
    {
        $stories = Story::with('category')
            ->orderBy('rate', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json($stories);
    }
}
