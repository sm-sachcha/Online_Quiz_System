<?php

namespace App\Events;

use App\Models\UserAnswer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        try {
            if ($this->answer && $this->answer->quizAttempt && $this->answer->quizAttempt->quiz) {
                return new Channel('quiz.' . $this->answer->quizAttempt->quiz_id);
            }
        } catch (\Exception $e) {
            Log::warning('AnswerSubmitted broadcast failed: ' . $e->getMessage());
        }
        return [];
    }

    public function broadcastAs()
    {
        return 'answer.submitted';
    }

    public function broadcastWith()
    {
        try {
            if (!$this->answer || !$this->answer->quizAttempt) {
                return [];
            }
            
            return [
                'answer_id' => $this->answer->id,
                'user_id' => $this->answer->quizAttempt->user_id,
                'user_name' => $this->answer->quizAttempt->user?->name ?? 'Guest',
                'question_id' => $this->answer->question_id,
                'is_correct' => $this->answer->is_correct,
                'points_earned' => $this->answer->points_earned,
                'submitted_at' => now(),
            ];
        } catch (\Exception $e) {
            Log::warning('AnswerSubmitted broadcastWith failed: ' . $e->getMessage());
            return [];
        }
    }
    
    public function shouldBroadcastWhen()
    {
        return !empty($this->broadcastOn());
    }
}
