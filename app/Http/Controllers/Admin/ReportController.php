<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\UserActivity;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    public function index()
    {
        return view('admin.reports.index');
    }

    public function quizPerformance(Request $request)
    {
        $request->validate([
            'quiz_id' => 'nullable|exists:quizzes,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        $query = QuizAttempt::with(['quiz', 'user', 'result']);

        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $attempts = $query->get();

        $completedAttempts = $attempts->filter(fn($attempt) => $attempt->status === 'completed');
        $passedAttempts = $completedAttempts->filter(fn($attempt) => $attempt->result && $attempt->result->passed);

        $summary = [
            'total_attempts' => $attempts->count(),
            'completed_attempts' => $completedAttempts->count(),
            'average_score' => round($attempts->avg('score') ?? 0, 2),
            'pass_rate' => $completedAttempts->count() > 0
                ? ($passedAttempts->count() / $completedAttempts->count()) * 100
                : 0
        ];

        $dailyStats = QuizAttempt::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as attempts'),
                DB::raw('AVG(score) as avg_score')
            )
            ->when($request->filled('quiz_id'), fn($q) => $q->where('quiz_id', $request->quiz_id))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topPerformers = QuizAttempt::with('user', 'result')
            ->where('status', 'completed')
            ->when($request->filled('quiz_id'), fn($q) => $q->where('quiz_id', $request->quiz_id))
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        $quizzes = Quiz::where('is_published', true)->get();

        return view('admin.reports.quiz-performance', compact('attempts', 'summary', 'dailyStats', 'topPerformers', 'quizzes'));
    }

    public function userActivity(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        $query = UserActivity::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->orderByDesc('created_at')->paginate(50);

        $mostCommonAction = UserActivity::select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->first();

        $peakHour = UserActivity::select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        $summary = [
            'total_activities' => $activities->total(),
            'unique_users' => $activities->unique('user_id')->count(),
            'most_common_action' => $mostCommonAction ? $mostCommonAction->action : 'N/A',
            'peak_hour' => $peakHour ? $peakHour->hour : 'N/A'
        ];

        $activityByDay = UserActivity::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $users = User::whereIn('role', ['user', 'admin'])->get();

        return view('admin.reports.user-activity', compact('activities', 'summary', 'activityByDay', 'users'));
    }

    public function systemOverview()
    {
        $totalUsers = User::count();
        $totalAdmins = User::whereIn('role', ['admin', 'master_admin'])->count();
        $totalQuizzes = Quiz::count();
        $publishedQuizzes = Quiz::where('is_published', true)->count();
        $totalAttempts = QuizAttempt::count();
        $completedAttempts = QuizAttempt::where('status', 'completed')->count();
        
        $recentRegistrations = User::where('role', 'user')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        
        $activeUsers = UserActivity::where('created_at', '>=', now()->subDays(7))
            ->distinct('user_id')
            ->count('user_id');
        
        $popularQuizzes = Quiz::withCount('attempts')
            ->orderByDesc('attempts_count')
            ->limit(5)
            ->get();
        
        $categoryDistribution = DB::table('quizzes')
            ->join('categories', 'quizzes.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as total'))
            ->groupBy('categories.name')
            ->get();
        
        $activityByDay = UserActivity::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reports.system-overview', compact(
            'totalUsers',
            'totalAdmins',
            'totalQuizzes',
            'publishedQuizzes',
            'totalAttempts',
            'completedAttempts',
            'recentRegistrations',
            'activeUsers',
            'popularQuizzes',
            'categoryDistribution',
            'activityByDay'
        ));
    }

    public function exportQuizReport(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'format' => 'required|in:csv,pdf'
        ]);

        $format = $request->input('format'); // ✅ fix for protected visibility
        $quiz = Quiz::findOrFail($request->quiz_id);
        $attempts = QuizAttempt::with('user', 'result')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->get();

        if ($format === 'csv') {
            $filename = 'quiz-report-' . $quiz->slug . '-' . now()->format('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($attempts, $quiz) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, [
                    'User Name', 
                    'Email', 
                    'Score', 
                    'Total Points', 
                    'Percentage', 
                    'Passed', 
                    'Correct Answers',
                    'Incorrect Answers',
                    'Started At', 
                    'Completed At'
                ]);

                foreach ($attempts as $attempt) {
                    $percentage = $quiz->total_points > 0 
                        ? round(($attempt->score / $quiz->total_points) * 100, 2) 
                        : 0;

                    fputcsv($file, [
                        $attempt->user->name,
                        $attempt->user->email,
                        $attempt->score,
                        $quiz->total_points,
                        $percentage,
                        $attempt->result && $attempt->result->passed ? 'Yes' : 'No',
                        $attempt->correct_answers,
                        $attempt->incorrect_answers,
                        $attempt->started_at,
                        $attempt->ended_at
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return back()->with('error', 'PDF export not implemented yet.');
    }

    public function exportUserActivity(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:csv'
        ]);

        $format = $request->input('format'); // ✅ fix
        $activities = UserActivity::with('user')
            ->whereDate('created_at', '>=', $request->date_from)
            ->whereDate('created_at', '<=', $request->date_to)
            ->orderByDesc('created_at')
            ->get();

        if ($format === 'csv') {
            $filename = 'user-activity-' . now()->format('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($activities) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'User Name', 
                    'User Email', 
                    'Action', 
                    'IP Address',
                    'Details',
                    'Created At'
                ]);

                foreach ($activities as $activity) {
                    fputcsv($file, [
                        $activity->user->name ?? 'N/A',
                        $activity->user->email ?? 'N/A',
                        $activity->action,
                        $activity->ip_address,
                        json_encode($activity->details),
                        $activity->created_at
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return back()->with('error', 'Unsupported format.');
    }
}