<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $endTime;

    public function __construct(Quiz $quiz)
    {
        $this->quiz = $quiz;
        $this->endTime = now();
    }

    public function broadcastOn()
    {
        return new PresenceChannel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'quiz.ended';
    }

    public function broadcastWith()
    {
        return [
            'quiz_id' => $this->quiz->id,
            'title' => $this->quiz->title,
            'end_time' => $this->endTime,
        ];
    }
}