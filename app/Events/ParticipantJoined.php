<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $participant;
    public $quiz;

    public function __construct($participant, Quiz $quiz)
    {
        $this->participant = $participant;
        $this->quiz = $quiz;
    }

    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'participant.joined';
    }

    public function broadcastWith()
    {
        $isGuest = (bool) ($this->participant->is_guest ?? (!isset($this->participant->user_id) || !$this->participant->user_id));
        $name = $isGuest
            ? ($this->participant->guest_name ?? 'Guest')
            : (optional($this->participant->user)->name ?? 'Unknown User');

        return [
            'participant' => [
                'id' => $this->participant->id ?? null,
                'user_id' => $this->participant->user_id ?? null,
                'name' => $name,
                'is_guest' => $isGuest,
                'status' => $this->participant->status ?? 'joined',
                'joined_at' => optional($this->participant->joined_at)->toIso8601String() ?? now()->toIso8601String(),
            ],
        ];
    }
}
