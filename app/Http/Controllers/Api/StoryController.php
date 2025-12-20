<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Story;
use App\Models\UserStoryListen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Open a story (record that user opened it)
     * 
     * Records that a user opened a story. If user has already opened this story before,
     * no limit checks are performed. Otherwise, checks if user has a plan or viewed stories
     * count is less than count_free_story setting.
     * 
     * @param string $id Story ID
     * @return JsonResponse
     */
    public function openStory(string $id): JsonResponse
    {
        $user = Auth::user();
        $story = Story::findOrFail($id);

        // Check if user has already opened this story before
        $listenRecord = UserStoryListen::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        // If already opened, allow access without limit checks
        if ($listenRecord) {
            // Update last_listened_at to current time
            $listenRecord->update([
                'last_listened_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'استوری با موفقیت باز شد',
                'can_view' => true,
                'listen_record' => $listenRecord->fresh(),
            ], 200);
        }

        // If not opened before, check limits
        // Check if user has active subscription
        $hasActiveSubscription = $user->hasActiveSubscription();
        
        // Get count_free_story setting
        $countFreeStory = (int) Setting::getByKey('count_free_story', 0);
        
        // Count how many stories user has viewed
        $viewedStoriesCount = $user->storyListens()->count();

        // Check if user can view (has subscription OR viewed count < free limit)
        $canView = $hasActiveSubscription || $viewedStoriesCount < $countFreeStory;

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'شما به تعداد مجاز استوری رایگان دسترسی داشته‌اید. لطفا اشتراک تهیه کنید.',
                'can_view' => false,
            ], 403);
        }

        // Create new record for first time opening
        $listenRecord = UserStoryListen::create([
            'user_id' => $user->id,
            'story_id' => $story->id,
            'opened_at' => now(),
            'last_listened_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'استوری با موفقیت باز شد',
            'can_view' => true,
            'listen_record' => $listenRecord,
        ], 200);
    }

    /**
     * Update story listening progress
     * 
     * Updates how much of the story the user has listened to
     * 
     * @param Request $request
     * @param string $id Story ID
     * @return JsonResponse
     */
    public function updateProgress(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listened_seconds' => 'required|integer|min:0',
            'is_completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $story = Story::findOrFail($id);

        // Find or create listen record
        $listenRecord = UserStoryListen::firstOrCreate(
            [
                'user_id' => $user->id,
                'story_id' => $story->id,
            ],
            [
                'opened_at' => now(),
            ]
        );

        // Update progress
        $listenRecord->update([
            'listened_seconds' => $request->listened_seconds,
            'is_completed' => $request->input('is_completed', false),
            'last_listened_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'پیشرفت با موفقیت به‌روزرسانی شد',
            'listen_record' => $listenRecord,
        ], 200);
    }

    /**
     * Get story status for user
     * 
     * Returns whether the user has viewed the story and details if they have
     * 
     * @param string $id Story ID
     * @return JsonResponse
     */
    public function getStoryStatus(string $id): JsonResponse
    {
        $user = Auth::user();
        $story = Story::findOrFail($id);

        $listenRecord = UserStoryListen::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        if (!$listenRecord) {
            return response()->json([
                'success' => true,
                'has_viewed' => false,
                'message' => 'کاربر این استوری را ندیده است',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'has_viewed' => true,
            'listen_record' => [
                'id' => $listenRecord->id,
                'listened_seconds' => $listenRecord->listened_seconds,
                'is_completed' => $listenRecord->is_completed,
                'opened_at' => $listenRecord->opened_at?->toIso8601String(),
                'last_listened_at' => $listenRecord->last_listened_at?->toIso8601String(),
            ],
            'message' => 'کاربر این استوری را دیده است',
        ], 200);
    }
}