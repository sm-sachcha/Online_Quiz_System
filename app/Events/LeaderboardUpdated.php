<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaderboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $leaderboard;

    public function __construct(Quiz $quiz, $leaderboard)
    {
        $this->quiz = $quiz;
        $this->leaderboard = $leaderboard;
    }

    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'leaderboard.updated';
    }

    public function broadcastWith()
    {
        return [
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'leaderboard' => $this->leaderboard,
            'updated_at' => now(),
        ];
    }
}
