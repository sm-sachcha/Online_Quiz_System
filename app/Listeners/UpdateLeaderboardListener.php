<?php

namespace App\Listeners;

use App\Events\AnswerSubmitted;
use App\Events\LeaderboardUpdated;
use App\Services\LeaderboardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $quizAttempt = $event->answer->quizAttempt;
        $quiz = $quizAttempt->quiz;
        
        $leaderboard = $this->leaderboardService->updateLeaderboard($quiz);
        
        broadcast(new LeaderboardUpdated($quiz, $leaderboard));
    }
}