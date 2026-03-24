<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Category;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::where('role', 'user')->count(),
            'total_admins' => User::whereIn('role', ['admin', 'master_admin'])->count(),
            'total_quizzes' => Quiz::count(),
            'total_categories' => Category::count(),
            'published_quizzes' => Quiz::where('is_published', true)->count(),
            'total_attempts' => QuizAttempt::count(),
            'average_score' => QuizAttempt::avg('score') ?? 0,
            'active_quizzes' => Quiz::where('is_published', true)
                ->where('ends_at', '>=', now())
                ->count()
        ];

        $recentQuizzes = Quiz::with('creator', 'category')
            ->latest()
            ->take(10)
            ->get();

        $recentUsers = User::where('role', 'user')
            ->latest()
            ->take(10)
            ->get();

        $topQuizzes = Quiz::withCount('attempts')
            ->orderByDesc('attempts_count')
            ->take(5)
            ->get();

        $activityData = UserActivity::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact(
            'stats', 
            'recentQuizzes', 
            'recentUsers', 
            'topQuizzes',
            'activityData'
        ));
    }
}