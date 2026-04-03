<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Leaderboard;
use App\Models\QuizAttempt;
use App\Models\UserProfile;
use App\Models\QuizParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaderboardService
{
    /**
     * Update leaderboard for a quiz - only the best score per user/participant
     */
    public function updateLeaderboard(Quiz $quiz)
    {
        try {
            // Get all completed attempts for this quiz
            $attempts = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->get();
            
            if ($attempts->isEmpty()) {
                Log::info('No completed attempts for leaderboard', ['quiz_id' => $quiz->id]);
                return [];
            }
            
            // Group by user_id or participant_id and get best score per user/participant
            $bestAttempts = $attempts->groupBy(function ($attempt) {
                if ($attempt->user_id) {
                    return 'user_' . $attempt->user_id;
                }
                if ($attempt->participant_id) {
                    return 'participant_' . $attempt->participant_id;
                }
                return 'unknown_' . $attempt->id;
            })->map(function ($userAttempts) {
                // For each user/participant, get the attempt with the highest score
                return $userAttempts->sortByDesc(function ($attempt) {
                    return $attempt->score;
                })->first();
            })->sortByDesc(function ($attempt) {
                return $attempt->score;
            })->values();
            
            $leaderboard = [];
            $rank = 1;
            
            DB::beginTransaction();
            try {
                // Delete all existing leaderboard entries for this quiz
                Leaderboard::where('quiz_id', $quiz->id)->delete();
                
                foreach ($bestAttempts as $attempt) {
                    $percentage = $quiz->total_points > 0 
                        ? round(($attempt->score / $quiz->total_points) * 100, 1)
                        : 0;
                    
                    $accuracy = $attempt->total_questions > 0 
                        ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                        : 0;
                    
                    // Get display name - this is the critical part
                    $displayName = $this->getDisplayName($attempt);
                    
                    $leaderboardData = [
                        'quiz_id' => $quiz->id,
                        'score' => $attempt->score,
                        'rank' => $rank,
                        'metadata' => [
                            'correct_answers' => $attempt->correct_answers,
                            'incorrect_answers' => $attempt->incorrect_answers,
                            'total_questions' => $attempt->total_questions,
                            'percentage' => $percentage,
                            'accuracy' => $accuracy,
                            'attempt_id' => $attempt->id,
                            'attempt_number' => $this->getAttemptNumber($attempt),
                            'completed_at' => $attempt->ended_at,
                            'time_taken' => $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0,
                            'display_name' => $displayName
                        ]
                    ];
                    
                    // Add user_id if exists (registered user)
                    if ($attempt->user_id) {
                        $leaderboardData['user_id'] = $attempt->user_id;
                    }
                    
                    // Add participant_id if exists (guest)
                    if ($attempt->participant_id) {
                        $leaderboardData['participant_id'] = $attempt->participant_id;
                    }
                    
                    Leaderboard::create($leaderboardData);
                    
                    $leaderboard[] = [
                        'rank' => $rank,
                        'user_id' => $attempt->user_id,
                        'participant_id' => $attempt->participant_id,
                        'name' => $displayName,
                        'score' => $attempt->score,
                        'percentage' => $percentage,
                        'accuracy' => $accuracy,
                    ];
                    
                    $rank++;
                }
                
                DB::commit();
                Log::info('Leaderboard updated successfully', [
                    'quiz_id' => $quiz->id,
                    'entries' => count($leaderboard)
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Leaderboard update failed: ' . $e->getMessage(), [
                    'quiz_id' => $quiz->id,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
            return $leaderboard;
            
        } catch (\Exception $e) {
            Log::error('Leaderboard update error: ' . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    /**
     * Get display name for attempt - THIS IS THE KEY FIX
     */
    private function getDisplayName($attempt)
    {
        // First check if it's a registered user
        if ($attempt->user_id) {
            $user = \App\Models\User::find($attempt->user_id);
            if ($user) {
                return $user->name;
            }
            return 'Unknown User';
        }
        
        // Check if it's a guest participant
        if ($attempt->participant_id) {
            $participant = QuizParticipant::find($attempt->participant_id);
            if ($participant && $participant->guest_name) {
                return $participant->guest_name;
            }
            // Try to get from the attempt's relation if not found
            if ($attempt->relationLoaded('participant') && $attempt->participant) {
                return $attempt->participant->guest_name ?? 'Guest';
            }
            return 'Guest';
        }
        
        return 'Unknown';
    }
    
    /**
     * Get the attempt number for a specific attempt
     */
    private function getAttemptNumber($attempt)
    {
        if ($attempt->user_id) {
            $attempts = QuizAttempt::where('user_id', $attempt->user_id)
                ->where('quiz_id', $attempt->quiz_id)
                ->where('status', 'completed')
                ->orderBy('created_at')
                ->get();
        } else if ($attempt->participant_id) {
            $attempts = QuizAttempt::where('participant_id', $attempt->participant_id)
                ->where('quiz_id', $attempt->quiz_id)
                ->where('status', 'completed')
                ->orderBy('created_at')
                ->get();
        } else {
            return 1;
        }
        
        foreach ($attempts as $index => $a) {
            if ($a->id == $attempt->id) {
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
     * Get participant's best attempt for a quiz
     */
    public function getParticipantBestAttempt($participantId, $quizId)
    {
        return QuizAttempt::where('participant_id', $participantId)
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->first();
    }
    
    /**
     * Get leaderboard for a quiz with names properly displayed
     */
    public function getLeaderboard(Quiz $quiz)
    {
        $leaderboard = Leaderboard::with(['user', 'participant'])
            ->where('quiz_id', $quiz->id)
            ->orderBy('rank')
            ->get()
            ->map(function ($entry) {
                // Get name from metadata first, then from relationships
                $name = $entry->metadata['display_name'] ?? null;
                
                if (!$name) {
                    if ($entry->user) {
                        $name = $entry->user->name;
                    } else if ($entry->participant) {
                        $name = $entry->participant->guest_name ?? 'Guest';
                    } else {
                        $name = 'Unknown';
                    }
                }
                
                return [
                    'rank' => $entry->rank,
                    'user_id' => $entry->user_id,
                    'participant_id' => $entry->participant_id,
                    'name' => $name,
                    'score' => $entry->score,
                    'percentage' => $entry->metadata['percentage'] ?? 0,
                    'accuracy' => $entry->metadata['accuracy'] ?? 0,
                    'metadata' => $entry->metadata
                ];
            });
        
        return $leaderboard;
    }
    
    /**
     * Get top leaderboard for a quiz
     */
    public function getTopLeaderboard(Quiz $quiz, $limit = 10)
    {
        $leaderboard = Leaderboard::with(['user', 'participant'])
            ->where('quiz_id', $quiz->id)
            ->orderBy('rank')
            ->take($limit)
            ->get()
            ->map(function ($entry) {
                // Get name from metadata first, then from relationships
                $name = $entry->metadata['display_name'] ?? null;
                
                if (!$name) {
                    if ($entry->user) {
                        $name = $entry->user->name;
                    } else if ($entry->participant) {
                        $name = $entry->participant->guest_name ?? 'Guest';
                    } else {
                        $name = 'Unknown';
                    }
                }
                
                return (object)[
                    'rank' => $entry->rank,
                    'user_id' => $entry->user_id,
                    'participant_id' => $entry->participant_id,
                    'name' => $name,
                    'score' => $entry->score,
                    'percentage' => $entry->metadata['percentage'] ?? 0,
                ];
            });
        
        return $leaderboard;
    }
    
    /**
     * Get user's rank for a quiz
     */
    public function getUserQuizRank($userId, $quizId)
    {
        $leaderboard = Leaderboard::where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->first();
        
        return $leaderboard ? $leaderboard->rank : null;
    }
    
    /**
     * Get participant's rank for a quiz
     */
    public function getParticipantQuizRank($participantId, $quizId)
    {
        $leaderboard = Leaderboard::where('quiz_id', $quizId)
            ->where('participant_id', $participantId)
            ->first();
        
        return $leaderboard ? $leaderboard->rank : null;
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
                    'quizzes_won' => $profile->quizzes_won,
                    'average_score' => $profile->quizzes_attempted > 0 
                        ? round($profile->total_points / $profile->quizzes_attempted, 1) 
                        : 0
                ];
            });
    }
    
    /**
     * Force refresh leaderboard for a quiz
     */
    public function refreshLeaderboard($quizId)
    {
        $quiz = Quiz::find($quizId);
        if ($quiz) {
            return $this->updateLeaderboard($quiz);
        }
        return [];
    }
}