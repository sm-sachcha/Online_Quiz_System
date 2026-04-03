<?php

namespace App\Listeners;

use App\Events\AnswerSubmitted;
use App\Events\LeaderboardUpdated;
use App\Services\LeaderboardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateLeaderboardListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    public function handle(AnswerSubmitted $event): void
    {
        try {
            $quizAttempt = $event->answer->quizAttempt;
            $quiz = $quizAttempt->quiz;
            
            // Only update leaderboard when quiz is completed or after each answer?
            // For performance, update only when attempt is completed
            if ($quizAttempt->status === 'completed') {
                $leaderboard = $this->leaderboardService->updateLeaderboard($quiz);
                broadcast(new LeaderboardUpdated($quiz, $leaderboard));
            }
        } catch (\Exception $e) {
            Log::error('UpdateLeaderboardListener failed: ' . $e->getMessage());
        }
    }
}