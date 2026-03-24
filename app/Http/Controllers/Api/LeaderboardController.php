<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\UserProfile;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    public function getQuizLeaderboard(Quiz $quiz)
    {
        $leaderboard = $this->leaderboardService->getLeaderboard($quiz);
        
        return response()->json([
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'leaderboard' => $leaderboard
        ]);
    }

    public function getGlobalLeaderboard()
    {
        $leaderboard = $this->leaderboardService->getGlobalLeaderboard();
        
        return response()->json([
            'leaderboard' => $leaderboard
        ]);
    }

    public function getUserRank(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        if (!$profile) {
            return response()->json(['rank' => null]);
        }

        $rank = UserProfile::where('total_points', '>', $profile->total_points)->count() + 1;
        
        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_points' => $profile->total_points,
            'rank' => $rank
        ]);
    }
}