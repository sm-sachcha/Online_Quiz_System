<?php

namespace App\Events;

use App\Models\UserAnswer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $answer;

    public function __construct(UserAnswer $answer)
    {
        $this->answer = $answer;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('quiz.' . $this->answer->quizAttempt->quiz_id);
    }

    public function broadcastAs()
    {
        return 'answer.submitted';
    }

    public function broadcastWith()
    {
        return [
            'answer_id' => $this->answer->id,
            'user_id' => $this->answer->quizAttempt->user_id,
            'user_name' => $this->answer->quizAttempt->user->name,
            'question_id' => $this->answer->question_id,
            'is_correct' => $this->answer->is_correct,
            'points_earned' => $this->answer->points_earned,
            'submitted_at' => now(),
        ];
    }
}