<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // ── Step 1: build a subquery that returns the best attempt ID
        // per (user_id OR participant_id) + quiz_id, along with attempt_count ──
        $bestIdsQuery = QuizAttempt::select(
                DB::raw('MAX(id) as best_id'),
                DB::raw('COUNT(*) as attempt_count'),
                'quiz_id',
                'user_id',
                'participant_id'
            )
            ->where('status', 'completed');

        // Scope to quizzes created by this admin if not master
        if (!$user->isMasterAdmin()) {
            $bestIdsQuery->whereHas('quiz', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        // Apply filters on the subquery too so counts stay consistent
        if ($request->filled('quiz_id')) {
            $bestIdsQuery->where('quiz_id', $request->quiz_id);
        }

        if ($request->filled('user_id')) {
            if ($request->user_id === 'guest') {
                $bestIdsQuery->whereNotNull('participant_id')->whereNull('user_id');
            } else {
                $bestIdsQuery->where('user_id', $request->user_id);
            }
        }

        if ($request->filled('date_from')) {
            $bestIdsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $bestIdsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Group by participant identity + quiz
        $bestIdsQuery->groupBy('quiz_id', 'user_id', 'participant_id');

        // Materialise as a collection so we can join back
        $bestRows = $bestIdsQuery->get();
        $bestIds        = $bestRows->pluck('best_id');
        $attemptCounts  = $bestRows->pluck('attempt_count', 'best_id');

        // ── Step 2: fetch the full attempt rows for those IDs ──
        $query = QuizAttempt::with(['user', 'participant', 'quiz', 'quiz.category', 'result'])
            ->whereIn('id', $bestIds);

        // Pass/fail filter must be applied here (on result relation)
        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $query->whereHas('result', fn($q) => $q->where('passed', true));
            } elseif ($request->status === 'failed') {
                $query->whereHas('result', fn($q) => $q->where('passed', false));
            }
        }

        $attempts = $query->orderByDesc('score')->paginate(20)->withQueryString();

        // Attach attempt_count onto each paginated item
        $attempts->getCollection()->transform(function ($attempt) use ($attemptCounts) {
            $attempt->attempt_count = $attemptCounts->get($attempt->id, 1);
            return $attempt;
        });

        // ── Quizzes & users for filter dropdowns ──
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
            if (!$user->isMasterAdmin() && $selectedQuiz && $selectedQuiz->created_by !== $user->id) {
                abort(403, 'You do not have permission to view results for this quiz.');
            }
        }

        return view('admin.results.index', compact('attempts', 'quizzes', 'users', 'selectedQuiz'));
    }

    public function show(QuizAttempt $attempt)
    {
        $user = Auth::user();

        if (!$user->isMasterAdmin() && $attempt->quiz->created_by !== $user->id) {
            abort(403, 'You do not have permission to view this result.');
        }

        $attempt->load(['user', 'participant', 'quiz', 'quiz.category', 'answers.question.options', 'result']);

        // Attempt count for this participant on this quiz
        $attemptCount = QuizAttempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->where(function ($q) use ($attempt) {
                if ($attempt->user_id) {
                    $q->where('user_id', $attempt->user_id);
                } else {
                    $q->where('participant_id', $attempt->participant_id);
                }
            })
            ->count();

        $userRank = null;
        if ($attempt->user_id) {
            $userRank = Leaderboard::where('quiz_id', $attempt->quiz_id)
                ->where('user_id', $attempt->user_id)
                ->value('rank');
        } elseif ($attempt->participant_id) {
            $userRank = Leaderboard::where('quiz_id', $attempt->quiz_id)
                ->where('participant_id', $attempt->participant_id)
                ->value('rank');
        }

        $totalParticipants = Leaderboard::where('quiz_id', $attempt->quiz_id)->count();
        if ($totalParticipants == 0) {
            $totalParticipants = QuizAttempt::where('quiz_id', $attempt->quiz_id)
                ->where('status', 'completed')
                ->count();
        }

        $percentage = $attempt->quiz->total_points > 0
            ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
            : 0;

        $timeTaken    = $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0;
        $minutes      = floor($timeTaken / 60);
        $seconds      = $timeTaken % 60;
        $timeFormatted = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";

        $isGuest   = is_null($attempt->user_id);
        $userName  = $isGuest ? ($attempt->participant->guest_name ?? 'Guest User') : ($attempt->user->name ?? 'Unknown User');
        $userEmail = $isGuest ? 'Guest User' : ($attempt->user->email ?? 'N/A');

        return view('admin.results.show', compact(
            'attempt',
            'attemptCount',
            'userRank',
            'totalParticipants',
            'percentage',
            'timeTaken',
            'timeFormatted',
            'isGuest',
            'userName',
            'userEmail'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();

        // Re-use same best-result logic
        $bestIdsQuery = QuizAttempt::select(
                DB::raw('MAX(id) as best_id'),
                DB::raw('COUNT(*) as attempt_count'),
                'quiz_id',
                'user_id',
                'participant_id'
            )
            ->where('status', 'completed');

        if (!$user->isMasterAdmin()) {
            $bestIdsQuery->whereHas('quiz', fn($q) => $q->where('created_by', $user->id));
        }

        if ($request->filled('quiz_id'))  { $bestIdsQuery->where('quiz_id', $request->quiz_id); }
        if ($request->filled('date_from')) { $bestIdsQuery->whereDate('created_at', '>=', $request->date_from); }
        if ($request->filled('date_to'))   { $bestIdsQuery->whereDate('created_at', '<=', $request->date_to); }

        if ($request->filled('user_id')) {
            if ($request->user_id === 'guest') {
                $bestIdsQuery->whereNotNull('participant_id')->whereNull('user_id');
            } else {
                $bestIdsQuery->where('user_id', $request->user_id);
            }
        }

        $bestIdsQuery->groupBy('quiz_id', 'user_id', 'participant_id');
        $bestRows      = $bestIdsQuery->get();
        $bestIds       = $bestRows->pluck('best_id');
        $attemptCounts = $bestRows->pluck('attempt_count', 'best_id');

        $query = QuizAttempt::with(['user', 'participant', 'quiz', 'quiz.category', 'result'])
            ->whereIn('id', $bestIds);

        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $query->whereHas('result', fn($q) => $q->where('passed', true));
            } elseif ($request->status === 'failed') {
                $query->whereHas('result', fn($q) => $q->where('passed', false));
            }
        }

        $attempts = $query->orderByDesc('score')->get();

        $quiz = $attempts->first()?->quiz ?? Quiz::find($request->quiz_id) ?? new Quiz(['title' => 'Results']);
        $filename = "{$quiz->title} (" . now()->format('d-m-Y') . ")" . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($attempts, $attemptCounts) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                // 'Date',
                'Participant Name',
                'Obtained Marks',
                'Total Marks',
                'Percentage',
                'Correct Answers',
                'Incorrect Answers',
                'Total Questions',
                'Status',
                // 'Attempts',
                'Rank',
            ]);

            foreach ($attempts as $attempt) {
                $percentage = $attempt->quiz->total_points > 0
                    ? round(($attempt->score / $attempt->quiz->total_points) * 100, 2)
                    : 0;

                $rank = null;
                if ($attempt->user_id) {
                    $rank = Leaderboard::where('quiz_id', $attempt->quiz_id)->where('user_id', $attempt->user_id)->value('rank');
                } elseif ($attempt->participant_id) {
                    $rank = Leaderboard::where('quiz_id', $attempt->quiz_id)->where('participant_id', $attempt->participant_id)->value('rank');
                }

                $isGuest  = is_null($attempt->user_id);
                $userName = $isGuest
                    ? ($attempt->participant ? $attempt->participant->guest_name : 'Guest User')
                    : ($attempt->user ? $attempt->user->name : 'Unknown User');

                $status = ($attempt->result && $attempt->result->passed) ? 'Passed' : 'Failed';

                fputcsv($file, [
                    // $attempt->created_at ? $attempt->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $userName,
                    $attempt->score,
                    $attempt->quiz->total_points,
                    $percentage,
                    $attempt->correct_answers,
                    $attempt->incorrect_answers,
                    $attempt->total_questions,
                    $status,
                    // $attemptCounts->get($attempt->id, 1),
                    $rank ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}