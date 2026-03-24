<?php

namespace App\Events;

use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDisconnected implements ShouldBroadcast
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
        return 'user.disconnected';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'disconnected_at' => now(),
        ];
    }
}