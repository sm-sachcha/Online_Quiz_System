<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttemptResultUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public QuizAttempt $attempt,
        public array $payload
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('quiz.attempt.' . $this->attempt->id);
    }

    public function broadcastAs(): string
    {
        return 'attempt.result.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'attempt_id' => $this->attempt->id,
            'quiz_id' => $this->attempt->quiz_id,
            'payload' => $this->payload,
        ];
    }
}
