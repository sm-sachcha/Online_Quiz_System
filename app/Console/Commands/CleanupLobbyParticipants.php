<?php

namespace App\Console\Commands;

use App\Models\QuizParticipant;
use Illuminate\Console\Command;

class CleanupLobbyParticipants extends Command
{
    protected $signature = 'lobby:cleanup';
    protected $description = 'Remove inactive participants from quiz lobbies';

    public function handle()
    {
        // Remove participants who haven't sent heartbeat in the last 30 seconds
        // Use updated_at if available, otherwise use created_at
        $removed = QuizParticipant::where('status', 'joined')
            ->where('updated_at', '<', now()->subSeconds(30))
            ->update([
                'status' => 'left',
                'left_at' => now()
            ]);

        $this->info("Removed {$removed} inactive participants from lobbies.");
        
        return 0;
    }
}