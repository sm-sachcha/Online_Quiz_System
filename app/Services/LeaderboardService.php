<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Leaderboard;
use App\Models\QuizAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Update leaderboard for a quiz, keeping the best score per user
     */
    public function updateLeaderboard(Quiz $quiz)
    {
        // Get all completed attempts, group by user, and get the best score for each user
        $attempts = QuizAttempt::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userAttempts) {
                // For each user, get the attempt with the highest score
                // If scores are equal, take the one with earliest completion time
                return $userAttempts->sortByDesc(function ($attempt) {
                    return $attempt->score . '.' . $attempt->ended_at->timestamp;
                })->first();
            })
            ->sortByDesc(function ($attempt) {
                return $attempt->score;
            })
            ->values();
        
        $leaderboard = [];
        $rank = 1;
        
        DB::beginTransaction();
        try {
            // Delete all existing leaderboard entries for this quiz
            Leaderboard::where('quiz_id', $quiz->id)->delete();
            
            foreach ($attempts as $attempt) {
                $percentage = $quiz->total_points > 0 
                    ? ($attempt->score / $quiz->total_points) * 100 
                    : 0;
                
                // Create new leaderboard entry
                $leaderboardEntry = Leaderboard::create([
                    'user_id' => $attempt->user_id,
                    'quiz_id' => $quiz->id,
                    'score' => $attempt->score,
                    'rank' => $rank,
                    'metadata' => [
                        'correct_answers' => $attempt->correct_answers,
                        'total_questions' => $attempt->total_questions,
                        'percentage' => round($percentage, 1),
                        'attempt_id' => $attempt->id,
                        'attempt_number' => $this->getAttemptNumber($attempt->user_id, $quiz->id, $attempt->id),
                        'completed_at' => $attempt->ended_at
                    ]
                ]);
                
                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $attempt->user_id,
                    'user_name' => $attempt->user->name,
                    'score' => $attempt->score,
                    'correct_answers' => $attempt->correct_answers,
                    'percentage' => round($percentage, 1),
                    'attempt_number' => $this->getAttemptNumber($attempt->user_id, $quiz->id, $attempt->id),
                    'best_score' => $this->isBestScore($attempt->user_id, $quiz->id, $attempt->score)
                ];
                
                $rank++;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Leaderboard update failed: ' . $e->getMessage());
            throw $e;
        }
        
        return $leaderboard;
    }
    
    /**
     * Get the attempt number for a user's quiz attempt
     */
    private function getAttemptNumber($userId, $quizId, $attemptId)
    {
        $attempts = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();
        
        foreach ($attempts as $index => $attempt) {
            if ($attempt->id == $attemptId) {
                return $index + 1;
            }
        }
        
        return 1;
    }
    
    /**
     * Check if this is the user's best score for this quiz
     */
    private function isBestScore($userId, $quizId, $currentScore)
    {
        $bestScore = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->max('score');
        
        return $currentScore >= ($bestScore ?? 0);
    }
    
    /**
     * Get user's best attempt for a quiz
     */
    public function getUserBestAttempt($userId, $quizId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->first();
    }
    
    /**
     * Check if user can retake quiz
     */
    public function canRetakeQuiz($userId, $quiz)
    {
        $completedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();
        
        return $completedAttempts < $quiz->max_attempts;
    }
    
    /**
     * Get user's remaining attempts
     */
    public function getRemainingAttempts($userId, $quiz)
    {
        $completedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();
        
        return max(0, $quiz->max_attempts - $completedAttempts);
    }
    
    public function getLeaderboard(Quiz $quiz)
    {
        return Leaderboard::with('user')
            ->where('quiz_id', $quiz->id)
            ->orderBy('rank')
            ->get()
            ->map(function ($entry) {
                return [
                    'rank' => $entry->rank,
                    'user_id' => $entry->user_id,
                    'user_name' => $entry->user->name,
                    'score' => $entry->score,
                    'metadata' => $entry->metadata
                ];
            });
    }
    
    public function getGlobalLeaderboard()
    {
        return UserProfile::orderByDesc('total_points')
            ->with('user')
            ->take(100)
            ->get()
            ->map(function ($profile, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $profile->user_id,
                    'user_name' => $profile->user->name,
                    'total_points' => $profile->total_points,
                    'quizzes_attempted' => $profile->quizzes_attempted,
                    'quizzes_won' => $profile->quizzes_won
                ];
            });
    }
    
    public function getUserQuizRank(Quiz $quiz, $userId)
    {
        $leaderboard = Leaderboard::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();
        
        if ($leaderboard) {
            return $leaderboard->rank;
        }
        
        return null;
    }
    
    /**
     * Get user's attempt history for a quiz
     */
    public function getUserAttemptHistory($userId, $quizId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attempt, $index) use ($quizId) {
                $totalAttempts = QuizAttempt::where('user_id', $attempt->user_id)
                    ->where('quiz_id', $quizId)
                    ->where('status', 'completed')
                    ->count();
                    
                return [
                    'attempt_number' => $totalAttempts - $index,
                    'score' => $attempt->score,
                    'percentage' => $attempt->quiz->total_points > 0 
                        ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
                        : 0,
                    'completed_at' => $attempt->ended_at,
                    'is_best' => $this->isBestScore($attempt->user_id, $quizId, $attempt->score)
                ];
            });
    }
}