<?php

namespace App\Services;

use App\Models\UserAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Log;

class AntiCheatService
{
    public function analyzeAnswer(UserAnswer $answer)
    {
        $cheatingScore = 0;
        $attempt = $answer->quizAttempt;
        
        // Skip for guest users if needed
        if (!$attempt->user_id) {
            return 0;
        }
        
        // Check answer time (too fast = suspicious)
        if ($answer->time_taken_seconds < 2) {
            $cheatingScore += 0.3;
        }
        
        // Check for pattern of correct answers (all correct = suspicious)
        $correctAnswersCount = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('is_correct', true)
            ->count();
        
        $totalAnswers = UserAnswer::where('quiz_attempt_id', $attempt->id)->count();
        
        if ($totalAnswers > 3 && $correctAnswersCount / $totalAnswers > 0.9) {
            $cheatingScore += 0.2;
        }
        
        // Check IP address for multiple attempts (only if IP exists)
        if ($attempt->ip_address) {
            $attemptsFromIp = QuizAttempt::where('ip_address', $attempt->ip_address)
                ->where('quiz_id', $attempt->quiz_id)
                ->count();
            
            if ($attemptsFromIp > 3) {
                $cheatingScore += 0.2;
            }
        }
        
        return min($cheatingScore, 1.0);
    }
    
    public function logCheatingEvent(QuizAttempt $attempt, $type, $details)
    {
        $logs = $attempt->cheating_logs ?? [];
        $logs[] = [
            'type' => $type,
            'details' => $details,
            'detected_at' => now()
        ];
        
        $attempt->update(['cheating_logs' => $logs]);
        
        if (count($logs) > 5) {
            $attempt->update(['status' => 'disqualified']);
        }
    }
}