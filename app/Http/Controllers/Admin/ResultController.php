<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $baseQuery = $this->buildCompletedAttemptsQuery($request, $user);
        $attemptMeta = $this->buildBestAttemptMeta((clone $baseQuery)->get([
            'id',
            'quiz_id',
            'user_id',
            'participant_id',
            'score',
            'ended_at',
            'created_at',
        ]));

        $attempts = QuizAttempt::query()
            ->with(['user', 'participant', 'quiz', 'quiz.category', 'result'])
            ->whereIn('id', $attemptMeta->keys())
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $attempts->getCollection()->transform(function ($attempt) use ($attemptMeta) {
            $meta = $attemptMeta->get($attempt->id, []);
            $attempt->attempt_count = $meta['attempt_count'] ?? 1;
            $attempt->attempt_number = $meta['attempt_number'] ?? 1;
            $attempt->is_best_result = true;
            return $attempt;
        });

        // ── Quizzes & users for filter dropdowns ──
        if ($user->isMasterAdmin()) {
            $quizzes = Quiz::latest()->get();
        } else {
            $quizzes = Quiz::where('created_by', $user->id)
                ->latest()
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
        $isAllQuizzesExport = !$request->filled('quiz_id');
        $attemptMeta = $this->buildBestAttemptMeta(
            $this->buildCompletedAttemptsQuery($request, $user)->get([
                'id',
                'quiz_id',
                'user_id',
                'participant_id',
                'score',
                'ended_at',
                'created_at',
            ])
        );

        $attempts = QuizAttempt::query()
            ->with(['user', 'participant', 'quiz', 'quiz.category', 'result'])
            ->whereIn('id', $attemptMeta->keys())
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attempt) use ($attemptMeta) {
                $meta = $attemptMeta->get($attempt->id, []);
                $attempt->attempt_count = $meta['attempt_count'] ?? 1;
                $attempt->attempt_number = $meta['attempt_number'] ?? 1;
                $attempt->is_best_result = true;
                return $attempt;
            });

        $leaderboardRanks = Leaderboard::query()
            ->whereIn('quiz_id', $attempts->pluck('quiz_id')->unique()->values())
            ->get()
            ->keyBy(function ($entry) {
                return $entry->quiz_id . ':' . ($entry->user_id ? 'user:' . $entry->user_id : 'participant:' . $entry->participant_id);
            });

        $attempts = $attempts
            ->map(function ($attempt) use ($leaderboardRanks) {
                $rankKey = $attempt->quiz_id . ':' . ($attempt->user_id ? 'user:' . $attempt->user_id : 'participant:' . $attempt->participant_id);
                $attempt->export_rank = $leaderboardRanks->get($rankKey)?->rank;
                return $attempt;
            })
            ->sort(function ($left, $right) use ($isAllQuizzesExport) {
                if ($isAllQuizzesExport && $left->quiz_id !== $right->quiz_id) {
                    return strcmp((string) optional($left->quiz)->title, (string) optional($right->quiz)->title);
                }

                $leftRank = $left->export_rank ?? PHP_INT_MAX;
                $rightRank = $right->export_rank ?? PHP_INT_MAX;

                if ($leftRank !== $rightRank) {
                    return $leftRank <=> $rightRank;
                }

                if ($left->score !== $right->score) {
                    return $right->score <=> $left->score;
                }

                return strcmp((string) $left->created_at, (string) $right->created_at);
            })
            ->values();
        $quiz = $attempts->first()?->quiz ?? Quiz::find($request->quiz_id) ?? new Quiz(['title' => 'Results']);

        $quizHeldDate = $quiz->scheduled_at?->format('d-m-Y')?? $attempts->first()?->started_at?->format('d-m-Y');

        $filename = $isAllQuizzesExport
            ? 'all results (' . now()->format('d-m-Y') . ').csv'
            : $quiz->title . ($quizHeldDate ? " ({$quizHeldDate})" : '') . '.csv';
             
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($attempts, $isAllQuizzesExport) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $header = [
                'Rank',
                'Participant Name',
                'Obtained Marks',
                'Total Marks',
                'Percentage',
                'Correct Answers',
                'Incorrect Answers',
                'Total Questions',
                'Status',
            ];

            if ($isAllQuizzesExport) {
                array_unshift($header, 'Quiz Name');
            }

            fputcsv($file, $header);

            foreach ($attempts as $attempt) {
                $percentage = $attempt->quiz->total_points > 0
                    ? round(($attempt->score / $attempt->quiz->total_points) * 100, 2)
                    : 0;

                $isGuest  = is_null($attempt->user_id);
                $userName = $isGuest
                    ? ($attempt->participant ? $attempt->participant->guest_name : 'Guest User')
                    : ($attempt->user ? $attempt->user->name : 'Unknown User');

                $status = ($attempt->result && $attempt->result->passed) ? 'Passed' : 'Failed';

                $row = [
                    $attempt->export_rank ?? 'N/A',
                    $userName,
                    $attempt->score,
                    $attempt->quiz->total_points,
                    $percentage,
                    $attempt->correct_answers,
                    $attempt->incorrect_answers,
                    $attempt->total_questions,
                    $status,
                ];

                if ($isAllQuizzesExport) {
                    array_unshift($row, $attempt->quiz?->title ?? 'N/A');
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function buildCompletedAttemptsQuery(Request $request, $user): Builder
    {
        $query = QuizAttempt::query()
            ->where('status', 'completed');

        if (!$user->isMasterAdmin()) {
            $query->whereHas('quiz', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }

        if ($request->filled('user_id')) {
            if ($request->user_id === 'guest') {
                $query->whereNotNull('participant_id')->whereNull('user_id');
            } else {
                $query->where('user_id', $request->user_id);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $query->whereHas('result', fn ($q) => $q->where('passed', true));
            } elseif ($request->status === 'failed') {
                $query->whereHas('result', fn ($q) => $q->where('passed', false));
            }
        }

        return $query;
    }

    private function buildBestAttemptMeta(Collection $attempts): Collection
    {
        $metaByAttemptId = collect();

        $attempts
            ->groupBy(fn ($attempt) => $this->participantKey($attempt->quiz_id, $attempt->user_id, $attempt->participant_id))
            ->each(function (Collection $group) use ($metaByAttemptId) {
                $orderedAttempts = $group
                    ->sortBy(function ($attempt) {
                        return ($attempt->created_at?->getTimestamp() ?? 0) . '-' . $attempt->id;
                    })
                    ->values();

                $bestAttempt = $group
                    ->sort(function ($left, $right) {
                        if ($left->score !== $right->score) {
                            return $right->score <=> $left->score;
                        }

                        $leftEndedAt = $left->ended_at?->getTimestamp() ?? PHP_INT_MAX;
                        $rightEndedAt = $right->ended_at?->getTimestamp() ?? PHP_INT_MAX;

                        if ($leftEndedAt !== $rightEndedAt) {
                            return $leftEndedAt <=> $rightEndedAt;
                        }

                        return $left->id <=> $right->id;
                    })
                    ->first();

                if ($bestAttempt) {
                    $attemptNumber = $orderedAttempts->search(fn ($attempt) => $attempt->id === $bestAttempt->id);

                    $metaByAttemptId->put($bestAttempt->id, [
                        'attempt_count' => $orderedAttempts->count(),
                        'attempt_number' => $attemptNumber === false ? 1 : $attemptNumber + 1,
                        'is_best_result' => true,
                    ]);
                }
            });

        return $metaByAttemptId;
    }

    private function participantKey(int $quizId, ?int $userId, ?int $participantId): string
    {
        if ($userId) {
            return "quiz:{$quizId}:user:{$userId}";
        }

        if ($participantId) {
            return "quiz:{$quizId}:participant:{$participantId}";
        }

        return "quiz:{$quizId}:attempt:unknown";
    }
}
