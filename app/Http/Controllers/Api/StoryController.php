<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Story Controller
 * 
 * Handles story-related operations including listing, filtering, and categorization
 */
class StoryController extends Controller
{
    /**
     * Get all stories
     * 
     * Returns a list of all available stories with their categories
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $stories = Story::with('category')->get();
        if ($stories->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No stories found']);
        }
        return response()->json(['success' => true, 'stories' => $stories]);
    }

    /**
     * Get single story
     * 
     * Returns details of a specific story by ID
     * 
     * @param string $id Story ID
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $story = Story::with('category')->findOrFail($id);
        return response()->json(['success' => true, 'story' => $story]);
    }

    /**
     * Get stories by category
     * 
     * Returns all stories belonging to a specific category
     * 
     * @param string $categoryId Category ID
     * @return JsonResponse
     */
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

    /**
     * Get all categories
     * 
     * Returns a list of all available story categories
     * 
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::all();
        return response()->json(['success' => true, 'categories' => $categories]);
    }

    /**
     * Get newest stories
     * 
     * Returns the 10 most recently created stories
     * 
     * @return JsonResponse
     */
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

    /**
     * Get best rated stories
     * 
     * Returns the 10 highest rated stories
     * 
     * @return JsonResponse
     */
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