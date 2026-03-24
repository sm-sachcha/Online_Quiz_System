<?php

namespace App\Events;

use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $quiz;

    public function __construct(User $user, Quiz $quiz)
    {
        $this->user = $user;
        $this->quiz = $quiz;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'participant.left';
    }

    public function broadcastWith()
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name
            ]
        ];
    }
}