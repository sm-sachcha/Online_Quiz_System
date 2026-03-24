<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Debug: Check if there are any quizzes in the database
        $allQuizzesCount = Quiz::count();
        $publishedQuizzesCount = Quiz::where('is_published', true)->count();
        
        \Log::info('Quiz counts:', [
            'all_quizzes' => $allQuizzesCount,
            'published_quizzes' => $publishedQuizzesCount
        ]);
        
        // Get available quizzes - Published and not expired
        $availableQuizzes = Quiz::where('is_published', true)
            ->where(function($query) {
                // Either no schedule OR scheduled start is in the past
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function($query) {
                // Either no end date OR ends in future
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->with(['category', 'creator'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get();
        
        \Log::info('Available quizzes before filtering:', [
            'count' => $availableQuizzes->count(),
            'quizzes' => $availableQuizzes->pluck('title')->toArray()
        ]);
        
        // Filter out quizzes where user has reached max attempts
        $filteredQuizzes = collect();
        foreach ($availableQuizzes as $quiz) {
            $attemptsCount = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count();
            
            if ($attemptsCount < $quiz->max_attempts) {
                $filteredQuizzes->push($quiz);
            }
        }
        $availableQuizzes = $filteredQuizzes;
        
        // Get recent attempts with results
        $recentAttempts = QuizAttempt::with(['quiz', 'quiz.category', 'result'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
        
        // Get all active categories with quiz counts
        $categories = Category::where('is_active', true)
            ->withCount(['quizzes' => function($query) {
                $query->where('is_published', true)
                    ->where(function($q) {
                        $q->whereNull('scheduled_at')
                            ->orWhere('scheduled_at', '<=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', now());
                    });
            }])
            ->orderBy('name')
            ->get();
        
        // Calculate user statistics
        $stats = [
            'total_quizzes_attempted' => QuizAttempt::where('user_id', $user->id)->count(),
            'average_score' => QuizAttempt::where('user_id', $user->id)->avg('score') ?? 0,
            'quizzes_passed' => QuizAttempt::where('user_id', $user->id)
                ->whereHas('result', function($query) {
                    $query->where('passed', true);
                })
                ->count(),
            'total_points' => $user->profile ? $user->profile->total_points : 0,
            'total_available_quizzes' => $availableQuizzes->count()
        ];
        
        // Get featured/random quizzes (3 random quizzes)
        $featuredQuizzes = Quiz::where('is_published', true)
            ->where(function($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->with('category')
            ->withCount('questions')
            ->inRandomOrder()
            ->take(3)
            ->get();
        
        // Get user's performance chart data (last 7 days)
        $performanceData = QuizAttempt::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(score) as avg_score'),
                DB::raw('COUNT(*) as attempts')
            )
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return view('user.dashboard', compact(
            'availableQuizzes', 
            'recentAttempts', 
            'categories',
            'stats',
            'featuredQuizzes',
            'performanceData'
        ));
    }
}