<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizLobbyUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $participants;
    public $totalParticipants;

    public function __construct(Quiz $quiz, $participants, $totalParticipants)
    {
        $this->quiz = $quiz;
        $this->participants = $participants;
        $this->totalParticipants = $totalParticipants;
    }

    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'lobby.updated';
    }

    public function broadcastWith()
    {
        return [
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'participants' => $this->participants,
            'total_participants' => $this->totalParticipants,
            'updated_at' => now(),
        ];
    }
}
