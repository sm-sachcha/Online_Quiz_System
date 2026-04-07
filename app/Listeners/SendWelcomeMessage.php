<?php

namespace App\Listeners;

use App\Events\ParticipantJoined;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeMessage implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ParticipantJoined $event): void
    {
        try {
            // Simple logging - no notification system needed
            $userName = 'Guest';
            $userId = 'guest';
            
            if ($event->user) {
                $userId = $event->user->id ?? 'guest';
                $userName = $event->user->name ?? 'Guest';
            }
            
            $quizTitle = $event->quiz->title ?? 'Unknown Quiz';
            $quizId = $event->quiz->id ?? 'unknown';
            
            Log::channel('single')->info('Participant joined quiz', [
                'user_id' => $userId,
                'user_name' => $userName,
                'quiz_id' => $quizId,
                'quiz_title' => $quizTitle,
                'joined_at' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            // Silently fail - don't break the queue
            Log::channel('single')->error('SendWelcomeMessage error: ' . $e->getMessage());
        }
    }
}