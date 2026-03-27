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

        $quiz = $attempt->quiz;
        
        $result = $this->getOrCalculateResult($attempt);
        $attemptStats = $this->getAttemptStatistics($attempt, $quiz);
        $leaderboardData = $this->getLeaderboardData($quiz, $attempt);
        $performanceMetrics = $this->getPerformanceMetrics($attempt);
        $attemptInfo = $this->getAttemptInfo($attempt, $quiz);
        $attemptHistory = $this->getAttemptHistory($quiz, $attempt);
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
                'canRetake' => $attemptInfo['can_retake'],
                'attemptHistory' => $attemptHistory
            ]
        ));
    }
    
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
    
    private function getAttemptStatistics(QuizAttempt $attempt, $quiz)
    {
        $percentage = $quiz->total_points > 0 
            ? round(($attempt->score / $quiz->total_points) * 100, 1)
            : 0;
        
        return ['percentage' => $percentage];
    }
    
    private function getLeaderboardData($quiz, $attempt)
    {
        $userRank = Leaderboard::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->value('rank');
        
        if (!$userRank) {
            $userRank = $this->calculateManualRank($quiz);
        }
        
        $topLeaderboard = $this->getTopLeaderboard($quiz, 10);
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
    
    private function calculateManualRank($quiz)
    {
        $bestScores = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userAttempts) {
                return $userAttempts->sortByDesc('score')->first();
            })
            ->sortByDesc('score')
            ->values();
        
        foreach ($bestScores as $index => $bestAttempt) {
            if ($bestAttempt->user_id == Auth::id()) {
                return $index + 1;
            }
        }
        
        return null;
    }
    
    private function getTopLeaderboard($quiz, $limit = 10)
    {
        $topLeaderboard = Leaderboard::with('user')
            ->where('quiz_id', $quiz->id)
            ->orderBy('rank')
            ->take($limit)
            ->get();
        
        if ($topLeaderboard->isEmpty()) {
            $bestAttempts = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->get()
                ->groupBy('user_id')
                ->map(function ($userAttempts) {
                    return $userAttempts->sortByDesc('score')->first();
                })
                ->sortByDesc('score')
                ->take($limit)
                ->values();
            
            $topLeaderboard = collect();
            $rank = 1;
            foreach ($bestAttempts as $attempt) {
                $user = \App\Models\User::find($attempt->user_id);
                if ($user) {
                    $percentage = $quiz->total_points > 0 
                        ? round(($attempt->score / $quiz->total_points) * 100, 1)
                        : 0;
                    
                    $topLeaderboard->push((object)[
                        'rank' => $rank,
                        'user' => $user,
                        'user_id' => $attempt->user_id,
                        'score' => $attempt->score,
                        'percentage' => $percentage
                    ]);
                }
                $rank++;
            }
        }
        
        return $topLeaderboard;
    }
    
    private function getAttemptHistory($quiz, $currentAttempt)
    {
        $allAttempts = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();
        
        $bestAttempt = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->first();
        
        $totalAttemptsCount = $allAttempts->count();
        
        return $allAttempts->map(function ($attempt, $index) use ($quiz, $bestAttempt, $totalAttemptsCount, $currentAttempt) {
            $percentage = $quiz->total_points > 0 
                ? round(($attempt->score / $quiz->total_points) * 100, 1)
                : 0;
            
            $accuracy = $attempt->total_questions > 0 
                ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                : 0;
            
            $attemptNumber = $totalAttemptsCount - $index;
            
            return [
                'attempt_number' => $attemptNumber,
                'attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'percentage' => $percentage,
                'accuracy' => $accuracy,
                'correct_answers' => $attempt->correct_answers,
                'incorrect_answers' => $attempt->incorrect_answers,
                'total_questions' => $attempt->total_questions,
                'passed' => $percentage >= $quiz->passing_score,
                'is_best' => $bestAttempt && $bestAttempt->id === $attempt->id,
                'is_current' => $currentAttempt->id === $attempt->id,
                'completed_at' => $attempt->ended_at,
            ];
        });
    }
    
    private function getPerformanceMetrics(QuizAttempt $attempt)
    {
        $accuracy = $attempt->total_questions > 0 
            ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
            : 0;
        
        return [
            'accuracy' => $accuracy,
            'time_taken' => $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0,
            'time_per_question' => $attempt->total_questions > 0 && $attempt->ended_at
                ? round($attempt->ended_at->diffInSeconds($attempt->started_at) / $attempt->total_questions, 1)
                : 0,
            'points_per_question' => $attempt->total_questions > 0
                ? round($attempt->score / $attempt->total_questions, 1)
                : 0,
        ];
    }
    
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
            
            $bestAccuracy = $bestAttempt->total_questions > 0 
                ? round(($bestAttempt->correct_answers / $bestAttempt->total_questions) * 100, 1)
                : 0;
            
            return [
                'score' => $bestAttempt->score,
                'percentage' => $bestPercentage,
                'accuracy' => $bestAccuracy,
                'correct_answers' => $bestAttempt->correct_answers,
                'total_questions' => $bestAttempt->total_questions,
                'completed_at' => $bestAttempt->ended_at,
            ];
        }
        
        return null;
    }
    
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
        
        $accuracy = $attempt->total_questions > 0 
            ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
            : 0;
        
        return view('user.results.certificate', compact(
            'attempt', 
            'result', 
            'quiz',
            'userRank', 
            'totalParticipants',
            'percentage',
            'accuracy'
        ));
    }
}