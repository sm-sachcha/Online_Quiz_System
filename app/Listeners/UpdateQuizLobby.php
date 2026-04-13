<?php

namespace App\Listeners;

use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
use App\Events\QuizLobbyUpdated;
use App\Models\QuizParticipant;
use Illuminate\Support\Facades\Log;

class UpdateQuizLobby
{
    public function handleParticipantJoined(ParticipantJoined $event): void
    {
        try {
            $this->broadcastLobbyUpdate($event->quiz);
        } catch (\Exception $e) {
            Log::error('UpdateQuizLobby (join) failed: ' . $e->getMessage());
        }
    }

    public function handleParticipantLeft(ParticipantLeft $event): void
    {
        try {
            $this->broadcastLobbyUpdate($event->quiz);
        } catch (\Exception $e) {
            Log::error('UpdateQuizLobby (left) failed: ' . $e->getMessage());
        }
    }

    private function broadcastLobbyUpdate($quiz)
    {
        try {
            $participants = QuizParticipant::with('user')
                ->where('quiz_id', $quiz->id)
                ->where('status', 'joined')
                ->get()
                ->map(function ($participant) {
                    $name = $participant->is_guest
                        ? ($participant->guest_name ?: 'Guest')
                        : (optional($participant->user)->name ?? 'Unknown User');

                    return [
                        'id' => $participant->id,
                        'user_id' => $participant->user_id,
                        'name' => $name,
                        'is_guest' => (bool) $participant->is_guest,
                        'status' => $participant->status,
                        'joined_at' => $participant->joined_at,
                    ];
                });

            $totalParticipants = $participants->count();
            
            broadcast(new QuizLobbyUpdated($quiz, $participants, $totalParticipants));
            Log::info('Lobby updated for quiz: ' . $quiz->id . ' - ' . $totalParticipants . ' participants');
        } catch (\Exception $e) {
            Log::error('broadcastLobbyUpdate failed: ' . $e->getMessage());
        }
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
