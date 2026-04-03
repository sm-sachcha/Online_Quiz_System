<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get categories assigned to this user
        // Fix: Use correct pivot table name 'category_user' and get category IDs
        $assignedCategoryIds = $user->assignedCategories()->pluck('categories.id')->toArray();
        
        // Get quizzes assigned directly to this user (if you have this relationship)
        $assignedQuizIds = [];
        if (method_exists($user, 'assignedQuizzes')) {
            $assignedQuizIds = $user->assignedQuizzes()->pluck('quizzes.id')->toArray();
        }
        
        // Log for debugging
        Log::info('User assigned categories', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'assigned_category_ids' => $assignedCategoryIds,
            'assigned_quiz_ids' => $assignedQuizIds
        ]);
        
        // Build query for quizzes
        $quizQuery = Quiz::where('is_published', true);
        
        // Apply access restrictions for non-admin users
        if (!$user->isAdmin()) {
            // If user has no assigned categories and no directly assigned quizzes, they see nothing
            if (empty($assignedCategoryIds) && empty($assignedQuizIds)) {
                $quizQuery->whereRaw('1 = 0'); // No quizzes
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
            // Check if quiz is scheduled for future
            if ($quiz->scheduled_at && $quiz->scheduled_at > now()) {
                $scheduledQuizzes->push($quiz);
            }
            // Check if quiz is expired
            elseif ($quiz->ends_at && $quiz->ends_at < now()) {
                $expiredQuizzes->push($quiz);
            }
            // Check if quiz is active and available
            else {
                // Check if user has reached max attempts
                $attemptsCount = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->count();
                
                if ($attemptsCount < $quiz->max_attempts) {
                    $availableQuizzes->push($quiz);
                }
            }
        }
        
        // Get recent attempts with results
        $recentAttempts = QuizAttempt::with(['quiz', 'quiz.category', 'result'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
        
        // Get categories that are assigned to this user
        if ($user->isAdmin()) {
            // Admin sees all categories
            $categories = Category::where('is_active', true)
                ->withCount(['quizzes' => function($query) {
                    $query->where('is_published', true);
                }])
                ->orderBy('name')
                ->get();
        } else {
            // Regular user only sees assigned categories
            if (empty($assignedCategoryIds)) {
                $categories = collect(); // Empty collection
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
        
        // Get featured/random quizzes (only from active quizzes)
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