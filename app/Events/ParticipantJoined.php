<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantJoined implements ShouldBroadcast
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
        return [
            'participant' => [
                'id' => $this->participant->id ?? null,
                'user_id' => $this->participant->user_id ?? null,
                'name' => $this->participant->guest_name ?? ($this->participant->name ?? 'Guest'),
                'is_guest' => !isset($this->participant->user_id) || !$this->participant->user_id,
                'status' => $this->participant->status ?? 'joined',
                'joined_at' => now(),
            ],
        ];
    }
}
