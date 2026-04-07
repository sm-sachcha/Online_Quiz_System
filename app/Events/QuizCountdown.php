<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizCountdown implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $seconds;

    public function __construct(Quiz $quiz, $seconds)
    {
        $this->quiz = $quiz;
        $this->seconds = $seconds;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'quiz.countdown';
    }

    public function broadcastWith()
    {
        return [
            'quiz_id' => $this->quiz->id,
            'seconds' => $this->seconds,
            'start_time' => now(),
        ];
    }
}