<?php

namespace App\Listeners;

use App\Events\UserDisconnected;
use App\Models\QuizParticipant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleUserDisconnect implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserDisconnected $event): void
    {
        QuizParticipant::where('quiz_id', $event->quiz->id)
            ->where('user_id', $event->user->id)
            ->update([
                'status' => 'disconnected',
                'left_at' => now()
            ]);
    }
}