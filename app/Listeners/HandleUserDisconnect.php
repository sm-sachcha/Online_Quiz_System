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
            // Check if participant exists before updating
            $participant = QuizParticipant::where('quiz_id', $event->quiz->id)
                ->where('user_id', $event->user->id)
                ->first();
            
            if ($participant) {
                $participant->update([
                    'status' => 'disconnected',
                    'left_at' => now()
                ]);
                Log::info('User disconnected: ' . $event->user->name . ' from quiz: ' . $event->quiz->title);
            } else {
                Log::info('No participant record found for user: ' . $event->user->id);
            }
        } catch (\Exception $e) {
            Log::error('HandleUserDisconnect failed: ' . $e->getMessage());
        }
    }
}