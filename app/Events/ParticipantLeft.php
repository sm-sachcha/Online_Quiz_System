<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\QuizParticipant;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use stdClass;

class ParticipantLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $participant;
    public $user;
    public $quiz;

    public function __construct($participant, Quiz $quiz)
    {
        if ($participant instanceof QuizParticipant) {
            $this->participant = [
                'id' => $participant->id,
                'user_id' => $participant->user_id,
                'name' => $participant->is_guest
                    ? ($participant->guest_name ?? 'Guest')
                    : (optional($participant->user)->name ?? 'Unknown User'),
                'is_guest' => (bool) $participant->is_guest,
            ];
        } elseif ($participant instanceof User) {
            $this->participant = [
                'id' => null,
                'user_id' => $participant->id,
                'name' => $participant->name,
                'is_guest' => false
            ];
        } elseif ($participant instanceof stdClass || (is_object($participant) && isset($participant->name))) {
            $this->participant = [
                'id' => null,
                'user_id' => null,
                'name' => $participant->name ?? 'Guest',
                'is_guest' => true
            ];
        } elseif (is_array($participant)) {
            $this->participant = [
                'id' => $participant['id'] ?? null,
                'user_id' => $participant['user_id'] ?? null,
                'name' => $participant['name'] ?? 'Guest',
                'is_guest' => $participant['is_guest'] ?? true,
            ];
        } else {
            $this->participant = [
                'id' => null,
                'user_id' => null,
                'name' => 'Guest',
                'is_guest' => true
            ];
        }

        $this->user = $this->participant;
        $this->quiz = $quiz;
    }

    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'participant.left';
    }

    public function broadcastWith()
    {
        return [
            'participant' => [
                'id' => $this->participant['id'],
                'user_id' => $this->participant['user_id'],
                'name' => $this->participant['name'],
                'is_guest' => $this->participant['is_guest'],
                'left_at' => now()->toIso8601String(),
            ],
        ];
    }
}
