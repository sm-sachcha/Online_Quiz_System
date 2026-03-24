<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $startTime;

    public function __construct(Quiz $quiz)
    {
        $this->quiz = $quiz;
        $this->startTime = now();
    }

    public function broadcastOn()
    {
        return new PresenceChannel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'quiz.started';
    }

    public function broadcastWith()
    {
        return [
            'quiz_id' => $this->quiz->id,
            'title' => $this->quiz->title,
            'start_time' => $this->startTime,
            'duration' => $this->quiz->duration_minutes,
        ];
    }
}