<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Leaderboard;
use App\Services\ResultService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    protected $resultService;
    protected $leaderboardService;

    public function __construct(ResultService $resultService, LeaderboardService $leaderboardService)
    {
        $this->resultService = $resultService;
        $this->leaderboardService = $leaderboardService;
    }

    public function show($quizId, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $attempt->result;
        
        if (!$result) {
            $result = $this->resultService->calculateResult($attempt);
        }

        // Update leaderboard to ensure ranks are calculated
        $this->leaderboardService->updateLeaderboard($attempt->quiz);
        
        // Get user's rank from leaderboard
        $userRank = Leaderboard::where('quiz_id', $attempt->quiz_id)
            ->where('user_id', Auth::id())
            ->value('rank');
        
        // If not found in leaderboard, calculate rank manually
        if (!$userRank) {
            $allScores = QuizAttempt::where('quiz_id', $attempt->quiz_id)
                ->where('status', 'completed')
                ->orderByDesc('score')
                ->orderBy('ended_at')
                ->get();
            
            $rank = 1;
            foreach ($allScores as $index => $a) {
                if ($a->user_id == Auth::id()) {
                    $userRank = $index + 1;
                    break;
                }
            }
        }
        
        // Get top 10 leaderboard entries
        $topLeaderboard = Leaderboard::with('user')
            ->where('quiz_id', $attempt->quiz_id)
            ->orderBy('rank')
            ->take(10)
            ->get();
        
        // If leaderboard is empty, calculate from attempts
        if ($topLeaderboard->isEmpty()) {
            $allAttempts = QuizAttempt::where('quiz_id', $attempt->quiz_id)
                ->where('status', 'completed')
                ->orderByDesc('score')
                ->orderBy('ended_at')
                ->get();
            
            $topLeaderboard = collect();
            $rank = 1;
            foreach ($allAttempts->take(10) as $a) {
                $user = \App\Models\User::find($a->user_id);
                $topLeaderboard->push((object)[
                    'rank' => $rank,
                    'user' => $user,
                    'user_id' => $a->user_id,
                    'score' => $a->score
                ]);
                $rank++;
            }
        }
        
        // Calculate total participants
        $totalParticipants = QuizAttempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->count();
        
        // Calculate percentage score
        $percentage = $attempt->quiz->total_points > 0 
            ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
            : 0;
        
        // Get performance metrics
        $performanceMetrics = [
            'accuracy' => $attempt->total_questions > 0 
                ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                : 0,
            'time_taken' => $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0,
            'time_per_question' => $attempt->total_questions > 0 && $attempt->ended_at
                ? round($attempt->ended_at->diffInSeconds($attempt->started_at) / $attempt->total_questions, 1)
                : 0,
        ];
        
        // Calculate remaining attempts
        $remainingAttempts = $attempt->quiz->max_attempts - QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->count();
        
        // Debug - log rank information
        \Log::info('Result page data', [
            'user_id' => Auth::id(),
            'quiz_id' => $attempt->quiz_id,
            'user_rank' => $userRank,
            'total_participants' => $totalParticipants,
            'top_leaderboard_count' => $topLeaderboard->count()
        ]);

        return view('user.quiz.result', compact(
            'attempt', 
            'result', 
            'userRank', 
            'topLeaderboard', 
            'totalParticipants',
            'percentage',
            'performanceMetrics',
            'remainingAttempts'
        ));
    }

    public function history()
    {
        $history = $this->resultService->getUserQuizHistory(Auth::id());
        
        return view('user.results.index', compact('history'));
    }

    public function certificate(QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $attempt->result;
        
        if (!$result || !$result->passed) {
            abort(404);
        }

        // Get user's rank for certificate
        $userRank = Leaderboard::where('quiz_id', $attempt->quiz_id)
            ->where('user_id', Auth::id())
            ->value('rank');
        
        $totalParticipants = QuizAttempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->count();
        
        return view('user.results.certificate', compact('attempt', 'result', 'userRank', 'totalParticipants'));
    }
}