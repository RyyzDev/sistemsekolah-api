<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;

// --- PPDB CONTROLLERS  ---
use App\Http\Controllers\PPDB\StudentController;
use App\Http\Controllers\PPDB\ParentController;
use App\Http\Controllers\PPDB\GradeController;
use App\Http\Controllers\PPDB\AdminPPDBController;

// --- SIAKAD CONTROLLERS ---
use App\Http\Controllers\Siakad\AuthController;
use App\Http\Controllers\Siakad\StudentSiakadController;
use App\Http\Controllers\Siakad\TeacherController;
use App\Http\Controllers\Siakad\ParentSiakadController;
use App\Http\Controllers\Siakad\AttendanceController;
use App\Http\Controllers\Siakad\GradeSiakadController;
use App\Http\Controllers\Siakad\ReportCardController;



  /**
    *  
    * Penerimaan Peserta Didik Baru (PPDB) ROUTES
    * 
    */

//webhook notification
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

    //Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function (){
        Route::get('/statistics', [AdminPPDBController::class, 'statistics']);
        Route::get('/students', [AdminPPDBController::class, 'getStudentBy']);
        Route::post('/students/{id}/verify', [AdminPPDBController::class, 'verifyStudent']);
        Route::post('/students{studentId}/documents/{documentId}/verify', [AdminPPDBController::class, 'verifyDocument']);       
        Route::get('/payments/summary/all', [AdminPPDBController::class, 'summary']);
        Route::post('/payments/{id}/refund', [AdminPPDBController::class, 'refund']); 
    });

    // Student Routes
    Route::prefix('students')->group(function () {
        Route::get('/me', [StudentController::class, 'me']); // Current user's student data
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/{id}', [StudentController::class, 'show']);
        Route::put('/{id}', [StudentController::class, 'update']);
        Route::post('/{id}/photo', [StudentController::class, 'uploadPhoto']);
        Route::post('/{id}/submit', [StudentController::class, 'submit']);
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
        // Route::get('/{id}/debug-status', [PaymentController::class, 'debugStatus']);
        Route::post('/{id}/sync-status', [PaymentController::class, 'syncStatus']);
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
        
    });

    
});


Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
    Route::post('/payments', [PaymentController::class, 'store']);
});





/**
  *  
  * Sistem Informasi Akademik (SIAKAD) ROUTES
  * 
  */

Route::prefix('siakad')->group(function () {
    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        
        // --- AUTH & PROFILE ---
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // --- DASHBOARDS (Role Based) ---
        Route::prefix('dashboards')->group(function () {
            Route::get('/admin', [DashboardController::class, 'admin'])->middleware('role:Admin');
            Route::get('/teacher', [DashboardController::class, 'teacher'])->middleware('role:Teacher');
            Route::get('/student', [DashboardController::class, 'student'])->middleware('role:Student');
            Route::get('/parent', [DashboardController::class, 'parent'])->middleware('role:Parent');
        });

        // --- NOTIFICATIONS ---
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']); // Fixed name
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::post('/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });

        // --- GLOBAL DATA ACCESS (Accessible by Auth Users) ---
        Route::get('/classrooms', [ClassroomController::class, 'index']);
        Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show']);
        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::get('/subjects/{subject}', [SubjectController::class, 'show']);

        // --- ACADEMIC RECORDS (Student, Parent, Teacher, Admin) ---
        Route::middleware('role:Student|Parent|Teacher|Admin')->group(function () {
            Route::get('/attendances', [AttendanceController::class, 'index']);
            Route::get('/attendances/summary/stats', [AttendanceController::class, 'getSummary']);
            Route::get('/attendances/{attendance}', [AttendanceController::class, 'show']);

            Route::get('/grades', [GradeController::class, 'index']);
            Route::get('/grades/{grade}', [GradeController::class, 'show']);
            
            Route::prefix('report-cards')->group(function () {
                Route::post('/generate', [ReportCardController::class, 'generate']);
                Route::post('/download-pdf', [ReportCardController::class, 'downloadPDF']);
            });
        });

        // --- TEACHER & ADMIN (Input Data) ---
        Route::middleware('role:Teacher|Admin')->group(function () {
            Route::post('/attendances/bulk', [AttendanceController::class, 'bulkStore']);
            Route::post('/grades/bulk', [GradeController::class, 'bulkStore']);
            Route::post('/grades/calculate-final', [GradeController::class, 'calculateFinalScore']);
            Route::get('/grades/classroom-summary/stats', [GradeController::class, 'getClassroomSummary']);
            
            // Resource routes (Teacher can often also update their own entry)
            Route::apiResource('attendances', AttendanceController::class)->except(['index', 'show']);
            Route::apiResource('grades', GradeController::class)->except(['index', 'show']);
        });

        // --- ADMIN ONLY (Master Data Management) ---
        Route::middleware('role:Admin')->group(function () {
            // Academic & Semesters
            Route::apiResource('academic-years', AcademicYearController::class);
            Route::post('/academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate']);
            Route::get('/current-academic-year', [AcademicYearController::class, 'getActive']);

            Route::apiResource('semesters', SemesterController::class);
            Route::post('/semesters/{semester}/activate', [SemesterController::class, 'activate']);
            Route::get('/current-semester', [SemesterController::class, 'getActive']);

            // Classroom Management
            Route::apiResource('classrooms', ClassroomController::class)->except(['index', 'show']);
            Route::prefix('classrooms/{classroom}')->group(function () {
                Route::post('/assign-teacher', [ClassroomController::class, 'assignTeacher']);
                Route::post('/remove-teacher', [ClassroomController::class, 'removeTeacher']);
                Route::post('/assign-student', [ClassroomController::class, 'assignStudent']);
                Route::post('/remove-student', [ClassroomController::class, 'removeStudent']);
            });

            // Master Resources
            Route::apiResource('subjects', SubjectController::class)->except(['index', 'show']);
            Route::apiResource('students', StudentController::class);
            Route::apiResource('teachers', TeacherController::class);
            Route::apiResource('parents', ParentController::class);
        });
    });
});