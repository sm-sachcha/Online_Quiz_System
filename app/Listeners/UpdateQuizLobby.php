<?php

namespace App\Listeners;

use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
use App\Events\QuizLobbyUpdated;
use App\Models\QuizParticipant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateQuizLobby implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleParticipantJoined(ParticipantJoined $event): void
    {
        $this->broadcastLobbyUpdate($event->quiz);
    }

    public function handleParticipantLeft(ParticipantLeft $event): void
    {
        $this->broadcastLobbyUpdate($event->quiz);
    }

    private function broadcastLobbyUpdate($quiz)
    {
        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'joined_at' => $participant->joined_at
                ];
            });

        $totalParticipants = $participants->count();

        broadcast(new QuizLobbyUpdated($quiz, $participants, $totalParticipants));
    }

    public function subscribe($events)
    {
        $events->listen(
            ParticipantJoined::class,
            [UpdateQuizLobby::class, 'handleParticipantJoined']
        );

        $events->listen(
            ParticipantLeft::class,
            [UpdateQuizLobby::class, 'handleParticipantLeft']
        );
    }
}