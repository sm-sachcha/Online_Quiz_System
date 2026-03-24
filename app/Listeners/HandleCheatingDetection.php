<?php

namespace App\Listeners;

use App\Events\AnswerSubmitted;
use App\Services\AntiCheatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $answer = $event->answer;
        $quizAttempt = $answer->quizAttempt;
        
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
    }
}