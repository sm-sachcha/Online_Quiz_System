<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizParticipantsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Quiz $quiz,
        public array $payload
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs(): string
    {
        return 'participants.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'quiz_id' => $this->quiz->id,
            'payload' => $this->payload,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
