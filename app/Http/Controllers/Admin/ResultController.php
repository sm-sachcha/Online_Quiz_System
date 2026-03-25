<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = QuizAttempt::with(['user', 'quiz', 'quiz.category', 'result'])
            ->where('status', 'completed');

        // If not Master Admin, only show results for quizzes they created
        if (!$user->isMasterAdmin()) {
            $query->whereHas('quiz', function($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        // Filter by quiz
        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by pass/fail
        if ($request->filled('passed')) {
            if ($request->passed == 'passed') {
                $query->whereHas('result', function($q) {
                    $q->where('passed', true);
                });
            } elseif ($request->passed == 'failed') {
                $query->whereHas('result', function($q) {
                    $q->where('passed', false);
                });
            }
        }

        $attempts = $query->orderByDesc('created_at')->paginate(20);
        
        // Get quizzes - filter by creator for General Admin
        if ($user->isMasterAdmin()) {
            $quizzes = Quiz::where('is_published', true)->orderBy('title')->get();
        } else {
            $quizzes = Quiz::where('is_published', true)
                ->where('created_by', $user->id)
                ->orderBy('title')
                ->get();
        }
        
        $users = User::where('role', 'user')->orderBy('name')->get();

        $selectedQuiz = null;
        if ($request->filled('quiz_id')) {
            $selectedQuiz = Quiz::find($request->quiz_id);
            // Check if General Admin has permission to view this quiz
            if (!$user->isMasterAdmin() && $selectedQuiz && $selectedQuiz->created_by !== $user->id) {
                abort(403, 'You do not have permission to view results for this quiz.');
            }
        }

        return view('admin.results.index', compact('attempts', 'quizzes', 'users', 'selectedQuiz'));
    }

    public function show(QuizAttempt $attempt)
    {
        $user = Auth::user();
        
        // Check if admin has permission to view this quiz
        if (!$user->isMasterAdmin() && $attempt->quiz->created_by !== $user->id) {
            abort(403, 'You do not have permission to view this result.');
        }
        
        $attempt->load(['user', 'quiz', 'answers.question.options', 'result']);
        
        $userRank = Leaderboard::where('quiz_id', $attempt->quiz_id)
            ->where('user_id', $attempt->user_id)
            ->value('rank');
        
        $totalParticipants = QuizAttempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->count();
        
        $percentage = $attempt->quiz->total_points > 0 
            ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
            : 0;
        
        $timeTaken = $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0;
        
        return view('admin.results.show', compact('attempt', 'userRank', 'totalParticipants', 'percentage', 'timeTaken'));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        
        $query = QuizAttempt::with(['user', 'quiz', 'result'])
            ->where('status', 'completed');

        // If not Master Admin, only export results for quizzes they created
        if (!$user->isMasterAdmin()) {
            $query->whereHas('quiz', function($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }

        $attempts = $query->get();

        $filename = 'quiz-results-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($attempts) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Date', 'User Name', 'User Email', 'Quiz Title', 'Obtained Marks', 
                'Total Marks', 'Percentage', 'Correct Answers', 'Incorrect Answers',
                'Time Taken (seconds)', 'Status', 'Rank'
            ]);
            
            foreach ($attempts as $attempt) {
                $percentage = $attempt->quiz->total_points > 0 
                    ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
                    : 0;
                
                $rank = Leaderboard::where('quiz_id', $attempt->quiz_id)
                    ->where('user_id', $attempt->user_id)
                    ->value('rank');
                
                $timeTaken = $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0;
                
                fputcsv($file, [
                    $attempt->created_at->format('Y-m-d H:i:s'),
                    $attempt->user->name,
                    $attempt->user->email,
                    $attempt->quiz->title,
                    $attempt->score,
                    $attempt->quiz->total_points,
                    $percentage,
                    $attempt->correct_answers,
                    $attempt->incorrect_answers,
                    $timeTaken,
                    ($attempt->result && $attempt->result->passed) ? 'Passed' : 'Failed',
                    $rank ?? 'N/A'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}