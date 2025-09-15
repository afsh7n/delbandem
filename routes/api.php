<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HeaderController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/pre-login', [AuthController::class, 'preLogin']);
    Route::post('/pre-login/resend-code', [AuthController::class, 'resendCode']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('stories')->group(function () {
    Route::get('/', [StoryController::class, 'index']);
    Route::get('/categories', [StoryController::class, 'getCategories']);
    Route::get('/new-stories', [StoryController::class, 'getNewStories']);
    Route::get('/best-stories', [StoryController::class, 'getBestStories']);
    Route::get('/category/{categoryId}', [StoryController::class, 'getByCategory']);
    Route::get('/{id}', [StoryController::class, 'show']);
});

Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/header', [HeaderController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::put('/add-to-favorites', [UserController::class, 'addToFavorites']);
        Route::put('/rate-story', [UserController::class, 'rateStory']);
        Route::get('/favorites', [UserController::class, 'getFavorites']);
    });
});
