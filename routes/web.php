<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\QuizLobbyController;
use App\Http\Controllers\User\QuizAttemptController;
use App\Http\Controllers\User\ResultController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\MasterAdmin\AdminManagementController;
use App\Http\Controllers\MasterAdmin\SystemSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});

// ==================== USER ROUTES ====================
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('results', [ResultController::class, 'history'])->name('results');
    Route::get('certificate/{attempt}', [ResultController::class, 'certificate'])->name('certificate');
    
    // Quiz routes
    Route::prefix('quiz')->name('quiz.')->group(function () {
        Route::get('lobby/{quiz}', [QuizLobbyController::class, 'index'])->name('lobby');
        Route::post('lobby/{quiz}/join', [QuizLobbyController::class, 'join'])->name('join');
        Route::post('lobby/{quiz}/leave', [QuizLobbyController::class, 'leave'])->name('leave');
        Route::get('lobby/{quiz}/participants', [QuizLobbyController::class, 'participants'])->name('participants');
        Route::post('lobby/{quiz}/heartbeat', [QuizLobbyController::class, 'heartbeat'])->name('heartbeat');
        
        Route::get('start/{quiz}', [QuizAttemptController::class, 'start'])->name('start');
        Route::get('attempt/{quiz}/{attempt}', [QuizAttemptController::class, 'attempt'])->name('attempt');
        Route::post('attempt/{quiz}/{attempt}/submit', [QuizAttemptController::class, 'submitAnswer'])->name('submit');
        Route::post('attempt/{quiz}/{attempt}/submit-multiple', [QuizAttemptController::class, 'submitMultipleAnswer'])->name('submit-multiple');
        Route::post('attempt/{quiz}/{attempt}/finish', [QuizAttemptController::class, 'finish'])->name('finish');
        
        Route::get('result/{quiz}/{attempt}', [ResultController::class, 'show'])->name('result');
        Route::get('attempts/{quiz}', [QuizAttemptController::class, 'attempts'])->name('attempts');
    });
});

// ==================== ADMIN ROUTES ====================
Route::middleware(['auth', 'role:admin,master_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Categories
    Route::resource('categories', CategoryController::class);
    
    // Quizzes
    Route::resource('quizzes', AdminQuizController::class);
    Route::post('quizzes/{quiz}/duplicate', [AdminQuizController::class, 'duplicate'])->name('quizzes.duplicate');
    Route::post('quizzes/{quiz}/toggle-publish', [AdminQuizController::class, 'togglePublish'])->name('quizzes.toggle-publish');
    Route::get('quizzes/{quiz}/participants', [AdminQuizController::class, 'participants'])->name('quizzes.participants');
    
    // Questions
    Route::prefix('quizzes/{quiz}/questions')->name('quizzes.questions.')->group(function () {
        Route::get('/', [QuestionController::class, 'index'])->name('index');
        Route::get('create', [QuestionController::class, 'create'])->name('create');
        Route::post('/', [QuestionController::class, 'store'])->name('store');
        Route::get('{question}/edit', [QuestionController::class, 'edit'])->name('edit');
        Route::put('{question}', [QuestionController::class, 'update'])->name('update');
        Route::delete('{question}', [QuestionController::class, 'destroy'])->name('destroy');
        Route::post('reorder', [QuestionController::class, 'reorder'])->name('reorder');
    });
    
    // Users
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('quiz-performance', [ReportController::class, 'quizPerformance'])->name('quiz-performance');
        Route::get('user-activity', [ReportController::class, 'userActivity'])->name('user-activity');
        Route::get('system-overview', [ReportController::class, 'systemOverview'])->name('system-overview');
        Route::get('export', [ReportController::class, 'exportQuizReport'])->name('export');
        Route::get('export-activity', [ReportController::class, 'exportUserActivity'])->name('export-activity');
    });
});

// ==================== MASTER ADMIN ROUTES ====================
Route::middleware(['auth', 'role:master_admin'])->prefix('master-admin')->name('master-admin.')->group(function () {
    Route::get('dashboard', function () {
        return view('master-admin.dashboard');
    })->name('dashboard');
    
    // Admin Management
    Route::resource('admins', AdminManagementController::class);
    Route::post('admins/{admin}/toggle-status', [AdminManagementController::class, 'toggleStatus'])->name('admins.toggle-status');
    Route::post('admins/{admin}/resend-welcome', [AdminManagementController::class, 'resendWelcome'])->name('admins.resend-welcome');
    Route::post('admins/{admin}/demote', [AdminManagementController::class, 'demoteToUser'])->name('admins.demote');
    Route::post('admins/promote', [AdminManagementController::class, 'promoteToAdmin'])->name('admins.promote');
    Route::get('admins/promotable/users', [AdminManagementController::class, 'getPromotableUsers'])->name('admins.promotable-users');
    
    // System Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::put('/', [SystemSettingsController::class, 'update'])->name('update');
        Route::get('maintenance', [SystemSettingsController::class, 'maintenance'])->name('maintenance');
        Route::post('maintenance/toggle', [SystemSettingsController::class, 'toggleMaintenance'])->name('maintenance.toggle');
        Route::get('cache', [SystemSettingsController::class, 'cache'])->name('cache');
        Route::post('cache/clear', [SystemSettingsController::class, 'clearCache'])->name('cache.clear');
        Route::get('logs', [SystemSettingsController::class, 'logs'])->name('logs');
        Route::post('logs/clear', [SystemSettingsController::class, 'clearLogs'])->name('logs.clear');
        Route::get('info', [SystemSettingsController::class, 'info'])->name('info');
    });
});

// Error routes
Route::get('unauthorized', function () {
    return view('errors.403');
})->name('unauthorized');

Route::fallback(function () {
    return view('errors.404');
});