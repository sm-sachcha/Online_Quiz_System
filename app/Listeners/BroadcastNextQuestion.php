<?php

namespace App\Listeners;

use App\Events\QuestionBroadcasted;
use App\Events\TimerSynced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastNextQuestion implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(QuestionBroadcasted $event): void
    {
        broadcast(new TimerSynced(
            $event->quiz,
            $event->question->id,
            $event->question->time_seconds
        ));
    }
}