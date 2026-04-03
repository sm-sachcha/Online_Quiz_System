<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Category;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isMasterAdmin = $user->isMasterAdmin();
        
        if ($isMasterAdmin) {
            // Master Admin sees all data
            $stats = [
                'total_users' => User::where('role', 'user')->count(),
                'total_admins' => User::whereIn('role', ['admin', 'master_admin'])->count(),
                'total_quizzes' => Quiz::count(),
                'total_categories' => Category::count(),
                'published_quizzes' => Quiz::where('is_published', true)->count(),
                'total_attempts' => QuizAttempt::count(),
                'average_score' => QuizAttempt::avg('score') ?? 0,
                'active_quizzes' => Quiz::where('is_published', true)
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>=', now());
                    })
                    ->count()
            ];
            
            // Recent quizzes - all quizzes
            $recentQuizzes = Quiz::with('creator', 'category')
                ->latest()
                ->take(10)
                ->get();
            
            // Recent users - all users
            $recentUsers = User::where('role', 'user')
                ->latest()
                ->take(10)
                ->get();
            
            // Top quizzes - all quizzes
            $topQuizzes = Quiz::withCount('attempts')
                ->orderByDesc('attempts_count')
                ->take(5)
                ->get();
            
            // Activity data - all activities
            $activityData = UserActivity::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
                
        } else {
            // Regular Admin sees ONLY their own data
            $adminId = $user->id;
            
            // Get quizzes created by this admin
            $quizIds = Quiz::where('created_by', $adminId)->pluck('id');
            
            $stats = [
                'total_users' => User::where('role', 'user')->count(),
                'total_admins' => User::whereIn('role', ['admin', 'master_admin'])->count(),
                'total_quizzes' => Quiz::where('created_by', $adminId)->count(),
                'total_categories' => Category::where('created_by', $adminId)->count(),
                'published_quizzes' => Quiz::where('created_by', $adminId)->where('is_published', true)->count(),
                'total_attempts' => QuizAttempt::whereIn('quiz_id', $quizIds)->count(),
                'average_score' => QuizAttempt::whereIn('quiz_id', $quizIds)->avg('score') ?? 0,
                'active_quizzes' => Quiz::where('created_by', $adminId)
                    ->where('is_published', true)
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>=', now());
                    })
                    ->count()
            ];
            
            // Recent quizzes - only admin's quizzes
            $recentQuizzes = Quiz::with('creator', 'category')
                ->where('created_by', $adminId)
                ->latest()
                ->take(10)
                ->get();
            
            // Recent users - all users
            $recentUsers = User::where('role', 'user')
                ->latest()
                ->take(10)
                ->get();
            
            // Top quizzes - only admin's quizzes
            $topQuizzes = Quiz::withCount('attempts')
                ->where('created_by', $adminId)
                ->orderByDesc('attempts_count')
                ->take(5)
                ->get();
            
            // Activity data - simplified version to avoid SQL errors
            $activityData = collect();
            
            if ($quizIds->isNotEmpty()) {
                // Get activity data for the last 7 days
                $activityData = UserActivity::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('count(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays(7))
                    ->whereIn('action', ['login', 'logout'])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            }
        }
        
        // Ensure we have data for all 7 days
        if ($activityData->isEmpty()) {
            $activityData = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $activityData->push((object)['date' => $date, 'count' => 0]);
            }
        }
        
        return view('admin.dashboard', compact(
            'stats', 
            'recentQuizzes', 
            'recentUsers', 
            'topQuizzes',
            'activityData',
            'isMasterAdmin'
        ));
    }
}