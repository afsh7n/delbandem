<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * User Controller
 * 
 * Handles authenticated user operations including profile, favorites, and story ratings
 */
class UserController extends Controller
{
    /**
     * Get current user profile
     * 
     * Returns the authenticated user's profile information
     * 
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();
        return response()->json(['success' => true, 'user' => $user]);
    }

    /**
     * Add story to favorites
     * 
     * Adds a story to the authenticated user's favorites list
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function addToFavorites(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'storyId' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $story = Story::findOrFail($request->storyId);

        // Check if already favorited
        if ($user->favoriteStories()->where('story_id', $story->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Story already in favorites'], 400);
        }

        $user->favoriteStories()->attach($story->id);

        return response()->json(['success' => true, 'message' => 'Story added to favorites']);
    }

    /**
     * Rate a story
     * 
     * Allows the authenticated user to rate a story (0-5 stars)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function rateStory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'storyId' => 'required|exists:stories,id',
            'rate' => 'required|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $story = Story::findOrFail($request->storyId);

        // Get current rated stories
        $ratedStories = $user->rated_stories ?? [];

        // Check if user has already rated this story
        $existingRateIndex = collect($ratedStories)->search(function ($item) use ($request) {
            return $item['storyId'] == $request->storyId;
        });

        if ($existingRateIndex !== false) {
            // Update existing rate
            $oldRate = $ratedStories[$existingRateIndex]['rate'];
            $ratedStories[$existingRateIndex]['rate'] = $request->rate;

            // Update story's average rate
            $totalRates = $story->total_rates;
            $currentTotal = $story->rate * $totalRates;
            $newTotal = $currentTotal - $oldRate + $request->rate;
            $story->rate = $newTotal / $totalRates;
        } else {
            // Add new rate
            $ratedStories[] = [
                'storyId' => $request->storyId,
                'rate' => $request->rate,
            ];

            // Update story's average rate
            $totalRates = $story->total_rates + 1;
            $currentTotal = $story->rate * $story->total_rates;
            $newTotal = $currentTotal + $request->rate;
            $story->rate = $newTotal / $totalRates;
            $story->total_rates = $totalRates;
        }

        $user->update(['rated_stories' => $ratedStories]);
        $story->save();

        return response()->json(['success' => true, 'message' => 'Story rated successfully']);
    }

    /**
     * Get user's favorite stories
     * 
     * Returns all stories favorited by the authenticated user
     * 
     * @return JsonResponse
     */
    public function getFavorites(): JsonResponse
    {
        $user = Auth::user();
        $favorites = $user->favoriteStories()->with('category')->get();

        return response()->json(['success' => true, 'favorites' => $favorites]);
    }
}