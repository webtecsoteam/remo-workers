<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/revenue-chart', [DashboardController::class, 'revenueChart']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

    // Users
    Route::apiResource('/users', UserController::class);
    Route::patch('/users/{id}/suspend', [UserController::class, 'suspend']);
    Route::patch('/users/{id}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{id}/verify', [UserController::class, 'verify']);
    Route::get('/users/{id}/jobs', [UserController::class, 'jobs']);
    Route::get('/users/{id}/payments', [UserController::class, 'payments']);

    // Jobs / Listings
    Route::apiResource('/jobs', JobController::class);
    Route::patch('/jobs/{id}/approve', [JobController::class, 'approve']);
    Route::patch('/jobs/{id}/reject', [JobController::class, 'reject']);
    Route::patch('/jobs/{id}/close', [JobController::class, 'close']);
    Route::patch('/jobs/{id}/flag', [JobController::class, 'flag']);

    // Payments & Transactions
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::patch('/payments/{id}/refund', [PaymentController::class, 'refund']);
    Route::patch('/payments/{id}/dispute', [PaymentController::class, 'resolveDispute']);
    Route::get('/payments/summary', [PaymentController::class, 'summary']);

    // Reports & Analytics
    Route::get('/reports/overview', [ReportController::class, 'overview']);
    Route::get('/reports/users', [ReportController::class, 'users']);
    Route::get('/reports/jobs', [ReportController::class, 'jobs']);
    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/export', [ReportController::class, 'export']);
});
