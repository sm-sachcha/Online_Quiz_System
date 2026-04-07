<?php

namespace App\Listeners;

use App\Events\ParticipantLeft;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendGoodbyeMessage implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ParticipantLeft $event): void
    {
        try {
            // Simple logging - no notification system needed
            $userName = 'Guest';
            $userId = 'guest';

            if (!empty($event->user) && is_array($event->user)) {
                $userId = $event->user['id'] ?? 'guest';
                $userName = $event->user['name'] ?? 'Guest';
            }
            
            $quizTitle = $event->quiz->title ?? 'Unknown Quiz';
            $quizId = $event->quiz->id ?? 'unknown';
            
            Log::channel('single')->info('Participant left quiz', [
                'user_id' => $userId,
                'user_name' => $userName,
                'quiz_id' => $quizId,
                'quiz_title' => $quizTitle,
                'left_at' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            // Silently fail - don't break the queue
            Log::channel('single')->error('SendGoodbyeMessage error: ' . $e->getMessage());
        }
    }
}
