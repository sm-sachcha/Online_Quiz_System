<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Leaderboard;
use App\Models\QuizAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function updateLeaderboard(Quiz $quiz)
    {
        $attempts = QuizAttempt::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->get();
        
        $leaderboard = [];
        $rank = 1;
        
        DB::beginTransaction();
        try {
            Leaderboard::where('quiz_id', $quiz->id)->delete();
            
            foreach ($attempts as $attempt) {
                $percentage = $quiz->total_points > 0 
                    ? ($attempt->score / $quiz->total_points) * 100 
                    : 0;
                
                Leaderboard::create([
                    'user_id' => $attempt->user_id,
                    'quiz_id' => $quiz->id,
                    'score' => $attempt->score,
                    'rank' => $rank,
                    'metadata' => [
                        'correct_answers' => $attempt->correct_answers,
                        'total_questions' => $attempt->total_questions,
                        'percentage' => $percentage,
                        'completed_at' => $attempt->ended_at
                    ]
                ]);
                
                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $attempt->user_id,
                    'user_name' => $attempt->user->name,
                    'score' => $attempt->score,
                    'correct_answers' => $attempt->correct_answers,
                    'percentage' => $percentage
                ];
                
                $rank++;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $leaderboard;
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
    
    public function getUserRank($userId, Quiz $quiz = null)
    {
        if ($quiz) {
            $entry = Leaderboard::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->first();
                
            return $entry ? $entry->rank : null;
        } else {
            $profile = UserProfile::where('user_id', $userId)->first();
            if (!$profile) return null;
            
            return UserProfile::where('total_points', '>', $profile->total_points)->count() + 1;
        }
    }
}