<?php

namespace App\Listeners;

use App\Events\ParticipantLeft;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendGoodbyeMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ParticipantLeft $event): void
    {
        \Illuminate\Support\Facades\Log::info("User {$event->user->name} left quiz {$event->quiz->title}");
    }
}