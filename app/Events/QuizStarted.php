<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStarted implements ShouldBroadcastNow
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
        return new Channel('quiz.' . $this->quiz->id);
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
            'start_time' => $this->startTime->toIso8601String(),
            'duration' => $this->quiz->duration_minutes,
            'redirect_url' => route('user.quiz.start', $this->quiz),
        ];
    }
}
