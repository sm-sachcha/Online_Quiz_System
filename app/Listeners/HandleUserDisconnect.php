<?php

namespace App\Listeners;

use App\Events\UserDisconnected;
use App\Models\QuizParticipant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleUserDisconnect implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserDisconnected $event): void
    {
        try {
            $userId = is_array($event->user) ? ($event->user['id'] ?? null) : null;
            $userName = is_array($event->user) ? ($event->user['name'] ?? 'Guest') : 'Guest';

            if (!$userId) {
                Log::info('No authenticated user ID provided for disconnect event.', [
                    'quiz_id' => $event->quiz->id,
                    'user_name' => $userName,
                ]);
                return;
            }

            // Check if participant exists before updating
            $participant = QuizParticipant::where('quiz_id', $event->quiz->id)
                ->where('user_id', $userId)
                ->first();
            
            if ($participant) {
                $participant->update([
                    'status' => 'disconnected',
                    'left_at' => now()
                ]);
                Log::info('User disconnected: ' . $userName . ' from quiz: ' . $event->quiz->title);
            } else {
                Log::info('No participant record found for user: ' . $userId);
            }
        } catch (\Exception $e) {
            Log::error('HandleUserDisconnect failed: ' . $e->getMessage());
        }
    }
}
