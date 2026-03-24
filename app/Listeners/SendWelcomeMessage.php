<?php

namespace App\Listeners;

use App\Events\ParticipantJoined;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ParticipantJoined $event): void
    {
        // Log welcome message
        \Illuminate\Support\Facades\Log::info("User {$event->user->name} joined quiz {$event->quiz->title}");
    }
}