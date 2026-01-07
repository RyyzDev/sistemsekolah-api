<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\PaymentController;


//notification midtrans (no auth)
Route::post('payments/notification', [PaymentController::class, 'notification']);

// Google OAuth Routes
Route::prefix('auth')->group(function () {
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback/applicant', [GoogleAuthController::class, 'handleGoogleCallback']);
    Route::post('google/token', [GoogleAuthController::class, 'loginWithGoogleToken']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Current User
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });

    // Student Routes
    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index']); // Admin only
        Route::get('/me', [StudentController::class, 'me']); // Current user's student data
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/statistics', [StudentController::class, 'statistics']); // Admin only
        Route::get('/{id}', [StudentController::class, 'show']);
        Route::put('/{id}', [StudentController::class, 'update']);
        Route::post('/{id}/photo', [StudentController::class, 'uploadPhoto']);
        Route::post('/{id}/submit', [StudentController::class, 'submit']);
        Route::post('/{id}/verify', [StudentController::class, 'verify']); // Admin only
        Route::delete('/{id}', [StudentController::class, 'destroy']);
    });

    // Parent Routes (nested under students)
    Route::prefix('students/{studentId}/parents')->group(function () {
        Route::get('/', [ParentController::class, 'index']);
        Route::post('/', [ParentController::class, 'store']);
        Route::get('/{parentId}', [ParentController::class, 'show']);
        Route::put('/{parentId}', [ParentController::class, 'update']);
        Route::delete('/{parentId}', [ParentController::class, 'destroy']);
    });

    // Achievement Routes (nested under students)
    Route::prefix('students/{studentId}/achievements')->group(function () {
        Route::get('/', [AchievementController::class, 'index']);
        Route::post('/', [AchievementController::class, 'store']);
        Route::get('/{achievementId}', [AchievementController::class, 'show']);
        Route::put('/{achievementId}', [AchievementController::class, 'update']);
        Route::delete('/{achievementId}', [AchievementController::class, 'destroy']);
    });

    // Document Routes (nested under students)
    Route::prefix('students/{studentId}/documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/{documentId}', [DocumentController::class, 'show']);
        Route::put('/{documentId}', [DocumentController::class, 'update']);
        Route::delete('/{documentId}', [DocumentController::class, 'destroy']);
        Route::post('/{documentId}/verify', [DocumentController::class, 'verify']); // Admin only
        Route::get('/{documentId}/download', [DocumentController::class, 'download']);
    });

    // Grade Routes (nested under students)
    Route::prefix('students/{studentId}/grades')->group(function () {
        Route::get('/', [GradeController::class, 'index']);
        Route::post('/', [GradeController::class, 'store']);
        Route::post('/bulk', [GradeController::class, 'storeBulk']);
        Route::get('/{gradeId}', [GradeController::class, 'show']);
        Route::put('/{gradeId}', [GradeController::class, 'update']);
        Route::delete('/{gradeId}', [GradeController::class, 'destroy']);
    });


    // Payment Routes
    Route::prefix('payments')->group(function () {
        // User Routes
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::post('/{id}/check-status', [PaymentController::class, 'checkStatus']);
        Route::get('/{id}/debug-status', [PaymentController::class, 'debugStatus']);
        Route::post('/{id}/sync-status', [PaymentController::class, 'syncStatus']);
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
        
        // Admin Routes
        Route::get('/summary/all', [PaymentController::class, 'summary']); // Admin only
        Route::post('/{id}/refund', [PaymentController::class, 'refund']); // Admin only
    });

    
});


Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
    Route::post('/payments', [PaymentController::class, 'store']);
});