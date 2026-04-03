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
        // Check if quiz ID matches
        if ($attempt->quiz_id != $quizId) {
            abort(404, 'Quiz not found for this attempt.');
        }
        
        // Handle guest attempts (user_id is null)
        if ($attempt->user_id === null) {
            // Check if this is a guest attempt via session
            $guestParticipantId = session('guest_participant_id');
            
            if ($attempt->participant_id == $guestParticipantId) {
                \Log::info('Guest accessing result', [
                    'attempt_id' => $attempt->id,
                    'participant_id' => $attempt->participant_id
                ]);
            } else {
                if ($attempt->created_at >= now()->subHours(1)) {
                    \Log::info('Recent guest attempt accessed', [
                        'attempt_id' => $attempt->id,
                        'created_at' => $attempt->created_at
                    ]);
                } else {
                    abort(403, 'This result is not available. Guest results expire after 1 hour.');
                }
            }
        } else {
            // Regular user check
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to view results.');
            }
            
            if ($attempt->user_id !== Auth::id()) {
                abort(403, 'You do not have permission to view this result.');
            }
        }

        $quiz = $attempt->quiz;
        
        // Get or calculate result
        $result = $this->getOrCalculateResult($attempt);
        
        // Get all data
        $attemptStats = $this->getAttemptStatistics($attempt, $quiz);
        $leaderboardData = $this->getLeaderboardData($quiz, $attempt);
        $performanceMetrics = $this->getPerformanceMetrics($attempt);
        $attemptInfo = $this->getAttemptInfo($attempt, $quiz);
        $attemptHistory = $this->getAttemptHistory($quiz, $attempt);
        $bestScoreInfo = $this->getBestScoreInfo($quiz, $attempt);

        // Debug log to verify rank
        \Log::info('Result data being passed to view', [
            'userRank' => $leaderboardData['userRank'] ?? null,
            'totalParticipants' => $leaderboardData['totalParticipants'] ?? null,
            'topLeaderboard_count' => $leaderboardData['topLeaderboard']->count()
        ]);

        // Merge all data for view
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
        }
        
        // ALWAYS update leaderboard when attempt is completed
        if ($attempt->status === 'completed') {
            \Log::info('Updating leaderboard for attempt', [
                'attempt_id' => $attempt->id,
                'quiz_id' => $attempt->quiz_id,
                'participant_id' => $attempt->participant_id,
                'user_id' => $attempt->user_id,
                'score' => $attempt->score
            ]);
            $this->leaderboardService->updateLeaderboard($attempt->quiz);
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
        $userRank = null;
        
        // Get rank based on attempt type
        if ($attempt->user_id !== null) {
            // Registered user
            $userRank = Leaderboard::where('quiz_id', $quiz->id)
                ->where('user_id', $attempt->user_id)
                ->value('rank');
            \Log::info('Looking up rank for registered user', [
                'user_id' => $attempt->user_id,
                'rank' => $userRank
            ]);
        } else if ($attempt->participant_id !== null) {
            // Guest participant
            $userRank = Leaderboard::where('quiz_id', $quiz->id)
                ->where('participant_id', $attempt->participant_id)
                ->value('rank');
            \Log::info('Looking up rank for guest participant', [
                'participant_id' => $attempt->participant_id,
                'rank' => $userRank
            ]);
        }
        
        // Get top 10 leaderboard with names
        $topLeaderboard = Leaderboard::with(['user', 'participant'])
            ->where('quiz_id', $quiz->id)
            ->orderBy('rank')
            ->take(10)
            ->get()
            ->map(function ($entry) use ($attempt) {
                $name = null;
                if ($entry->user) {
                    $name = $entry->user->name;
                } else if ($entry->participant) {
                    $name = $entry->participant->guest_name ?? 'Guest';
                } else {
                    $name = 'Unknown';
                }
                
                // Mark current user's entry
                $isCurrentUser = false;
                if ($attempt->user_id && $entry->user_id == $attempt->user_id) {
                    $isCurrentUser = true;
                } else if ($attempt->participant_id && $entry->participant_id == $attempt->participant_id) {
                    $isCurrentUser = true;
                }
                
                return (object)[
                    'rank' => $entry->rank,
                    'user_id' => $entry->user_id,
                    'participant_id' => $entry->participant_id,
                    'name' => $name,
                    'score' => $entry->score,
                    'percentage' => $entry->metadata['percentage'] ?? 0,
                    'is_current_user' => $isCurrentUser
                ];
            });
        
        $totalParticipants = Leaderboard::where('quiz_id', $quiz->id)->count();
        
        return [
            'userRank' => $userRank,
            'topLeaderboard' => $topLeaderboard,
            'totalParticipants' => $totalParticipants,
        ];
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
        // Guest users don't have attempt info
        if (!Auth::check()) {
            return [
                'attempt_number' => 1,
                'total_attempts' => 1,
                'remaining_attempts' => $quiz->max_attempts - 1,
                'is_best_score' => true,
                'can_retake' => $quiz->max_attempts > 1,
            ];
        }
        
        $allAttempts = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();
        
        // Find attempt number
        $attemptNumber = 1;
        foreach ($allAttempts as $index => $a) {
            if ($a->id == $attempt->id) {
                $attemptNumber = $index + 1;
                break;
            }
        }
        
        $totalAttempts = $allAttempts->count();
        
        // Find best attempt
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
        // Guest users don't have best score info
        if (!Auth::check()) {
            return null;
        }
        
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
    
    private function getAttemptHistory($quiz, $currentAttempt)
    {
        // Guest users don't have history
        if (!Auth::check()) {
            return collect();
        }
        
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
            abort(404, 'Certificate not available. You did not pass this quiz.');
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