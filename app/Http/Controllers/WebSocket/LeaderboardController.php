<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Events\LeaderboardUpdated;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    public function broadcastLeaderboard(Quiz $quiz)
    {
        $leaderboard = $this->leaderboardService->getLeaderboard($quiz);
        
        broadcast(new LeaderboardUpdated($quiz, $leaderboard))->toOthers();

        return response()->json(['success' => true]);
    }

    public function getLiveLeaderboard(Quiz $quiz)
    {
        $leaderboard = $this->leaderboardService->getLeaderboard($quiz);
        
        return response()->json([
            'quiz_id' => $quiz->id,
            'leaderboard' => $leaderboard
        ]);
    }
}