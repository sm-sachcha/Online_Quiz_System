<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerSynced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $questionId;
    public $timeRemaining;

    public function __construct(Quiz $quiz, $questionId, $timeRemaining)
    {
        $this->quiz = $quiz;
        $this->questionId = $questionId;
        $this->timeRemaining = $timeRemaining;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'timer.synced';
    }

    public function broadcastWith()
    {
        return [
            'quiz_id' => $this->quiz->id,
            'question_id' => $this->questionId,
            'time_remaining' => $this->timeRemaining,
            'synced_at' => now(),
        ];
    }
}