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
        // Verify ownership
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $quiz = $attempt->quiz;
        
        // Calculate result if not already calculated
        $result = $this->getOrCalculateResult($attempt);
        
        // Get attempt statistics
        $attemptStats = $this->getAttemptStatistics($attempt, $quiz);
        
        // Get leaderboard data
        $leaderboardData = $this->getLeaderboardData($quiz, $attempt);
        
        // Get performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($attempt);
        
        // Get attempt information
        $attemptInfo = $this->getAttemptInfo($attempt, $quiz);
        
        // Get user's best score info
        $bestScoreInfo = $this->getBestScoreInfo($quiz, $attempt);

        return view('user.quiz.result', array_merge(
            $attemptStats,
            $leaderboardData,
            [
                'attempt' => $attempt,
                'result' => $result,
                'quiz' => $quiz,
                'performanceMetrics' => $performanceMetrics,
                'timeTakenFormatted' => $this->formatTime($performanceMetrics['time_taken']),
                'bestScoreInfo' => $bestScoreInfo,
                'attemptNumber' => $attemptInfo['attempt_number'],
                'totalAttempts' => $attemptInfo['total_attempts'],
                'remainingAttempts' => $attemptInfo['remaining_attempts'],
                'isBestScore' => $attemptInfo['is_best_score'],
                'canRetake' => $attemptInfo['can_retake']
            ]
        ));
    }
    
    /**
     * Get or calculate result for the attempt
     */
    private function getOrCalculateResult(QuizAttempt $attempt)
    {
        $result = $attempt->result;
        
        if (!$result) {
            $result = $this->resultService->calculateResult($attempt);
            
            if ($attempt->status === 'completed') {
                $this->leaderboardService->updateLeaderboard($attempt->quiz);
            }
        }
        
        return $result;
    }
    
    /**
     * Get attempt statistics
     */
    private function getAttemptStatistics(QuizAttempt $attempt, $quiz)
    {
        $percentage = $quiz->total_points > 0 
            ? round(($attempt->score / $quiz->total_points) * 100, 1)
            : 0;
        
        return [
            'percentage' => $percentage,
        ];
    }
    
    /**
     * Get leaderboard data
     */
    private function getLeaderboardData($quiz, $attempt)
    {
        // Get user's rank
        $userRank = Leaderboard::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->value('rank');
        
        // Calculate rank manually if not found
        if (!$userRank) {
            $userRank = $this->calculateManualRank($quiz);
        }
        
        // Get top 10 leaderboard
        $topLeaderboard = $this->getTopLeaderboard($quiz);
        
        // Get total participants
        $totalParticipants = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count('user_id');
        
        return [
            'userRank' => $userRank,
            'topLeaderboard' => $topLeaderboard,
            'totalParticipants' => $totalParticipants,
        ];
    }
    
    /**
     * Calculate rank manually
     */
    private function calculateManualRank($quiz)
    {
        $allScores = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->get();
        
        foreach ($allScores as $index => $a) {
            if ($a->user_id == Auth::id()) {
                return $index + 1;
            }
        }
        
        return null;
    }
    
    /**
     * Get top 10 leaderboard entries
     */
    private function getTopLeaderboard($quiz)
    {
        $topLeaderboard = Leaderboard::with('user')
            ->where('quiz_id', $quiz->id)
            ->orderBy('rank')
            ->take(3)
            ->get();
        
        // If leaderboard is empty, calculate from attempts
        if ($topLeaderboard->isEmpty()) {
            $allAttempts = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->orderByDesc('score')
                ->orderBy('ended_at')
                ->get();
            
            $topLeaderboard = collect();
            $rank = 1;
            foreach ($allAttempts->take(3) as $a) {
                $user = \App\Models\User::find($a->user_id);
                if ($user) {
                    $topLeaderboard->push((object)[
                        'rank' => $rank,
                        'user' => $user,
                        'user_id' => $a->user_id,
                        'score' => $a->score
                    ]);
                }
                $rank++;
            }
        }
        
        return $topLeaderboard;
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(QuizAttempt $attempt)
    {
        return [
            'accuracy' => $attempt->total_questions > 0 
                ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                : 0,
            'time_taken' => $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0,
            'time_per_question' => $attempt->total_questions > 0 && $attempt->ended_at
                ? round($attempt->ended_at->diffInSeconds($attempt->started_at) / $attempt->total_questions, 1)
                : 0,
            'points_per_question' => $attempt->total_questions > 0
                ? round($attempt->score / $attempt->total_questions, 1)
                : 0,
        ];
    }
    
    /**
     * Get attempt information
     */
    private function getAttemptInfo(QuizAttempt $attempt, $quiz)
    {
        $allAttempts = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();
        
        $attemptNumber = 1;
        foreach ($allAttempts as $index => $a) {
            if ($a->id == $attempt->id) {
                $attemptNumber = $index + 1;
                break;
            }
        }
        
        $totalAttempts = $allAttempts->count();
        
        $bestAttempt = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->first();
        
        $isBestScore = $bestAttempt && $bestAttempt->id === $attempt->id;
        $remainingAttempts = max(0, $quiz->max_attempts - $totalAttempts);
        
        return [
            'attempt_number' => $attemptNumber,
            'total_attempts' => $totalAttempts,
            'remaining_attempts' => $remainingAttempts,
            'is_best_score' => $isBestScore,
            'can_retake' => $remainingAttempts > 0 && $attempt->status === 'completed',
        ];
    }
    
    /**
     * Get best score information
     */
    private function getBestScoreInfo($quiz, $attempt)
    {
        $bestAttempt = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->first();
        
        if ($bestAttempt && $bestAttempt->id != $attempt->id) {
            $bestPercentage = $quiz->total_points > 0 
                ? round(($bestAttempt->score / $quiz->total_points) * 100, 1)
                : 0;
            
            return [
                'score' => $bestAttempt->score,
                'percentage' => $bestPercentage,
                'completed_at' => $bestAttempt->ended_at,
            ];
        }
        
        return null;
    }
    
    /**
     * Format seconds into readable time
     */
    private function formatTime($seconds)
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes} min {$remainingSeconds} sec";
        }
        
        return "{$seconds} sec";
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

        $quiz = $attempt->quiz;
        
        // Get user's rank for certificate
        $userRank = Leaderboard::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->value('rank');
        
        $totalParticipants = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count('user_id');
        
        $percentage = $quiz->total_points > 0 
            ? round(($attempt->score / $quiz->total_points) * 100, 1)
            : 0;
        
        return view('user.results.certificate', compact(
            'attempt', 
            'result', 
            'quiz',
            'userRank', 
            'totalParticipants',
            'percentage'
        ));
    }
}