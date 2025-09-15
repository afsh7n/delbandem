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
        if ($stories->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No stories found']);
        }
        return response()->json(['success' => true, 'stories' => $stories]);
    }

    public function show(string $id): JsonResponse
    {
        $story = Story::with('category')->findOrFail($id);
        return response()->json(['success' => true, 'story' => $story]);
    }

    public function getByCategory(string $categoryId): JsonResponse
    {
        $stories = Story::with('category')
            ->where('category_id', $categoryId)
            ->get();

        if ($stories->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No stories found for this category']);
        }
        return response()->json(['success' => true, 'stories' => $stories]);
    }

    public function getCategories(): JsonResponse
    {
        $categories = Category::all();
        return response()->json(['success' => true, 'categories' => $categories]);
    }

    public function getNewStories(): JsonResponse
    {
        $stories = Story::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($stories->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No new stories found']);
        }
        return response()->json(['success' => true, 'stories' => $stories]);
    }

    public function getBestStories(): JsonResponse
    {
        $stories = Story::with('category')
            ->orderBy('rate', 'desc')
            ->limit(10)
            ->get();

        if ($stories->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No best stories found']);
        }
        return response()->json(['success' => true, 'stories' => $stories]);
    }
}