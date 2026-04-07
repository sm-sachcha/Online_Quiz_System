<?php

namespace App\Events;

use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use stdClass;

class UserDisconnected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $quiz;

    public function __construct($user, Quiz $quiz)
    {
        if ($user instanceof User) {
            $this->user = [
                'id' => $user->id,
                'name' => $user->name,
                'is_guest' => false
            ];
        } elseif ($user instanceof stdClass || (is_object($user) && isset($user->name))) {
            $this->user = [
                'id' => null,
                'name' => $user->name ?? 'Guest',
                'is_guest' => true
            ];
        } elseif (is_array($user)) {
            $this->user = $user;
        } else {
            $this->user = [
                'id' => null,
                'name' => 'Guest',
                'is_guest' => true
            ];
        }
        
        $this->quiz = $quiz;
    }

    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'user.disconnected';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user['id'],
            'user_name' => $this->user['name'],
            'is_guest' => $this->user['is_guest'],
            'disconnected_at' => now(),
        ];
    }
}
