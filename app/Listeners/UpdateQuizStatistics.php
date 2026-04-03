<?php

namespace App\Listeners;

use App\Events\AnswerSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateQuizStatistics implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AnswerSubmitted $event): void
    {
        try {
            $quizAttempt = $event->answer->quizAttempt;
            
            // Check if user exists (not a guest)
            if (!$quizAttempt->user_id) {
                // Guest user - no profile to update
                return;
            }
            
            $user = $quizAttempt->user;
            
            if (!$user || !$user->profile) {
                // User has no profile (shouldn't happen, but handle gracefully)
                Log::warning('User profile not found for user_id: ' . ($quizAttempt->user_id ?? 'null'));
                return;
            }
            
            $profile = $user->profile;
            
            // Update total points
            $profile->increment('total_points', $event->answer->points_earned);
            
            // Check if this attempt is completed (all questions answered)
            $totalQuestions = $quizAttempt->quiz->questions()->count();
            $answeredCount = $quizAttempt->answers()->count();
            
            if ($answeredCount >= $totalQuestions && $quizAttempt->status === 'completed') {
                $profile->increment('quizzes_attempted');
                
                // Check if user passed
                if ($quizAttempt->result && $quizAttempt->result->passed) {
                    $profile->increment('quizzes_won');
                }
            }
            
            $profile->save();
            
        } catch (\Exception $e) {
            Log::error('UpdateQuizStatistics failed: ' . $e->getMessage(), [
                'answer_id' => $event->answer->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}