<?php

namespace App\Listeners;

use App\Events\AnswerSubmitted;
use App\Services\AntiCheatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleCheatingDetection implements ShouldQueue
{
    use InteractsWithQueue;

    protected $antiCheatService;

    public function __construct(AntiCheatService $antiCheatService)
    {
        $this->antiCheatService = $antiCheatService;
    }

    public function handle(AnswerSubmitted $event): void
    {
        try {
            $answer = $event->answer;
            $quizAttempt = $answer->quizAttempt;
            
            // Skip cheating detection for guest users if needed
            if (!$quizAttempt->user_id) {
                return;
            }
            
            $cheatingScore = $this->antiCheatService->analyzeAnswer($answer);
            
            if ($cheatingScore > 0.7) {
                $cheatingLogs = $quizAttempt->cheating_logs ?? [];
                $cheatingLogs[] = [
                    'question_id' => $answer->question_id,
                    'score' => $cheatingScore,
                    'detected_at' => now(),
                    'reason' => 'Suspicious answer pattern detected'
                ];
                
                $quizAttempt->update(['cheating_logs' => $cheatingLogs]);
            }
        } catch (\Exception $e) {
            Log::error('HandleCheatingDetection failed: ' . $e->getMessage());
        }
    }
}