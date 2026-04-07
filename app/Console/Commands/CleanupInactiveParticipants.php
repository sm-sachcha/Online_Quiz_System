<?php

namespace App\Console\Commands;

use App\Models\QuizParticipant;
use Illuminate\Console\Command;

class CleanupInactiveParticipants extends Command
{
    protected $signature = 'participants:cleanup';
    protected $description = 'Remove inactive participants from all quiz lobbies';

    public function handle()
    {
        $cutoffTime = now()->subSeconds(60);
        
        // Find inactive participants
        $inactiveParticipants = QuizParticipant::where('status', 'joined')
            ->where('updated_at', '<', $cutoffTime)
            ->get();
        
        $count = 0;
        
        foreach ($inactiveParticipants as $participant) {
            $participant->status = 'left';
            $participant->left_at = now();
            $participant->save();
            $count++;
        }

        $this->info("Removed {$count} inactive participants.");
        
        return 0;
    }
}