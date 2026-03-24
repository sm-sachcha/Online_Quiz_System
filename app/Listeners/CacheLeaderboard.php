<?php

namespace App\Listeners;

use App\Events\LeaderboardUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class CacheLeaderboard implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaderboardUpdated $event): void
    {
        $cacheKey = 'leaderboard_quiz_' . $event->quiz->id;
        Cache::put($cacheKey, $event->leaderboard, now()->addMinutes(5));
    }
}