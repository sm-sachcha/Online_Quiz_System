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
     * Update leaderboard for a quiz - only update if new score is better
     */
    public function updateLeaderboard(Quiz $quiz)
    {
        // Get all completed attempts, get the best score per user
        $bestAttempts = QuizAttempt::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userAttempts) {
                // For each user, get the attempt with the highest score
                return $userAttempts->sortByDesc(function ($attempt) {
                    return $attempt->score;
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
            // Get existing leaderboard entries
            $existingEntries = Leaderboard::where('quiz_id', $quiz->id)
                ->get()
                ->keyBy('user_id');
            
            foreach ($bestAttempts as $attempt) {
                $percentage = $quiz->total_points > 0 
                    ? round(($attempt->score / $quiz->total_points) * 100, 1)
                    : 0;
                
                $accuracy = $attempt->total_questions > 0 
                    ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                    : 0;
                
                $existingEntry = $existingEntries->get($attempt->user_id);
                
                // Only update if this is a new entry or if score is higher than existing
                if (!$existingEntry || $attempt->score > $existingEntry->score) {
                    Leaderboard::updateOrCreate(
                        [
                            'quiz_id' => $quiz->id,
                            'user_id' => $attempt->user_id
                        ],
                        [
                            'score' => $attempt->score,
                            'rank' => $rank,
                            'metadata' => [
                                'correct_answers' => $attempt->correct_answers,
                                'incorrect_answers' => $attempt->incorrect_answers,
                                'total_questions' => $attempt->total_questions,
                                'percentage' => $percentage,
                                'accuracy' => $accuracy,
                                'attempt_id' => $attempt->id,
                                'attempt_number' => $this->getAttemptNumber($attempt->user_id, $quiz->id, $attempt->id),
                                'completed_at' => $attempt->ended_at
                            ]
                        ]
                    );
                }
                
                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $attempt->user_id,
                    'user_name' => $attempt->user->name,
                    'score' => $attempt->score,
                    'percentage' => $percentage,
                    'accuracy' => $accuracy,
                    'correct_answers' => $attempt->correct_answers,
                    'total_questions' => $attempt->total_questions,
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
     * Get the attempt number for a specific attempt
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
     * Get all attempts for a user on a quiz
     */
    public function getUserAttempts($userId, $quizId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attempt, $index) use ($quizId, $userId) {
                $totalAttempts = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quizId)
                    ->where('status', 'completed')
                    ->count();
                
                $percentage = $attempt->quiz->total_points > 0 
                    ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
                    : 0;
                
                $accuracy = $attempt->total_questions > 0 
                    ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                    : 0;
                
                $isBest = $this->isBestScore($userId, $quizId, $attempt->score);
                
                return [
                    'attempt_number' => $totalAttempts - $index,
                    'attempt_id' => $attempt->id,
                    'score' => $attempt->score,
                    'percentage' => $percentage,
                    'accuracy' => $accuracy,
                    'correct_answers' => $attempt->correct_answers,
                    'incorrect_answers' => $attempt->incorrect_answers,
                    'total_questions' => $attempt->total_questions,
                    'passed' => $percentage >= $attempt->quiz->passing_score,
                    'is_best' => $isBest,
                    'completed_at' => $attempt->ended_at,
                    'created_at' => $attempt->created_at,
                ];
            });
    }
    
    /**
     * Check if a score is the user's best
     */
    private function isBestScore($userId, $quizId, $score)
    {
        $bestScore = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->max('score');
        
        return $score >= ($bestScore ?? 0);
    }
    
    /**
     * Get user's rank for a quiz
     */
    public function getUserRank($userId, $quizId)
    {
        $leaderboard = Leaderboard::where('quiz_id', $quizId)
            ->orderBy('rank')
            ->get();
        
        foreach ($leaderboard as $entry) {
            if ($entry->user_id == $userId) {
                return $entry->rank;
            }
        }
        
        return null;
    }
    
    /**
     * Get leaderboard for a quiz
     */
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
                    'percentage' => $entry->metadata['percentage'] ?? 0,
                    'accuracy' => $entry->metadata['accuracy'] ?? 0,
                    'metadata' => $entry->metadata
                ];
            });
    }
    
    /**
     * Get global leaderboard
     */
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
}