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
        
        // Get categories assigned to this user
        $assignedCategoryIds = $user->assignedCategories()->pluck('category_id')->toArray();
        
        // Get quizzes assigned directly to this user
        $assignedQuizIds = $user->assignedQuizzes()->pluck('quiz_id')->toArray();
        
        // Build query for available quizzes
        $quizQuery = Quiz::where('is_published', true)
            ->where(function($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
        
        // Apply access restrictions for non-admin users
        if (!$user->isAdmin()) {
            if (empty($assignedCategoryIds) && empty($assignedQuizIds)) {
                $quizQuery->whereRaw('1 = 0');
            } else {
                $quizQuery->where(function($q) use ($assignedCategoryIds, $assignedQuizIds) {
                    if (!empty($assignedCategoryIds)) {
                        $q->whereIn('category_id', $assignedCategoryIds);
                    }
                    if (!empty($assignedQuizIds)) {
                        $q->orWhereIn('id', $assignedQuizIds);
                    }
                });
            }
        }
        
        $allQuizzes = $quizQuery->with(['category', 'creator'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Separate quizzes into categories
        $availableQuizzes = collect();
        $scheduledQuizzes = collect();
        $expiredQuizzes = collect();
        
        foreach ($allQuizzes as $quiz) {
            if ($quiz->scheduled_at && $quiz->scheduled_at > now()) {
                $scheduledQuizzes->push($quiz);
            } elseif ($quiz->ends_at && $quiz->ends_at < now()) {
                $expiredQuizzes->push($quiz);
            } else {
                $attemptsCount = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->count();
                
                if ($attemptsCount < $quiz->max_attempts) {
                    $availableQuizzes->push($quiz);
                }
            }
        }
        
        // Get recent attempts
        $recentAttempts = QuizAttempt::with(['quiz', 'quiz.category', 'result'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
        
        // Get categories that are assigned to this user
        if ($user->isAdmin()) {
            $categories = Category::where('is_active', true)
                ->withCount(['quizzes' => function($query) {
                    $query->where('is_published', true);
                }])
                ->orderBy('name')
                ->get();
        } else {
            if (empty($assignedCategoryIds)) {
                $categories = collect();
            } else {
                $categories = Category::where('is_active', true)
                    ->whereIn('id', $assignedCategoryIds)
                    ->withCount(['quizzes' => function($query) {
                        $query->where('is_published', true);
                    }])
                    ->orderBy('name')
                    ->get();
            }
        }
        
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
            'total_available_quizzes' => $availableQuizzes->count(),
            'total_scheduled_quizzes' => $scheduledQuizzes->count()
        ];
        
        // Get featured quizzes
        if ($user->isAdmin()) {
            $featuredQuizzes = Quiz::where('is_published', true)
                ->where(function($q) {
                    $q->whereNull('scheduled_at')
                        ->orWhere('scheduled_at', '<=', now());
                })
                ->where(function($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->with('category')
                ->withCount('questions')
                ->inRandomOrder()
                ->take(3)
                ->get();
        } else {
            if (empty($assignedCategoryIds) && empty($assignedQuizIds)) {
                $featuredQuizzes = collect();
            } else {
                $featuredQuizzes = Quiz::where('is_published', true)
                    ->where(function($q) use ($assignedCategoryIds, $assignedQuizIds) {
                        if (!empty($assignedCategoryIds)) {
                            $q->whereIn('category_id', $assignedCategoryIds);
                        }
                        if (!empty($assignedQuizIds)) {
                            $q->orWhereIn('id', $assignedQuizIds);
                        }
                    })
                    ->where(function($q) {
                        $q->whereNull('scheduled_at')
                            ->orWhere('scheduled_at', '<=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', now());
                    })
                    ->with('category')
                    ->withCount('questions')
                    ->inRandomOrder()
                    ->take(3)
                    ->get();
            }
        }
        
        // Get performance data
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
            'scheduledQuizzes',
            'expiredQuizzes',
            'recentAttempts', 
            'categories',
            'stats',
            'featuredQuizzes',
            'performanceData'
        ));
    }
}