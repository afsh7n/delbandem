<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

/**
 * Notification Controller
 * 
 * Handles notification-related operations
 */
class NotificationController extends Controller
{
    /**
     * Get all notifications
     * 
     * Returns a list of all notifications ordered by creation date
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'notifications' => $notifications]);
    }
}