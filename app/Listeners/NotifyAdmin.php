<?php

namespace App\Listeners;

use App\Events\QuizEnded;
use App\Events\QuizStarted;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdmin implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleQuizStarted(QuizStarted $event)
    {
        Log::info("Quiz '{$event->quiz->title}' has started.");
    }

    public function handleQuizEnded(QuizEnded $event)
    {
        Log::info("Quiz '{$event->quiz->title}' has ended.");
    }

    public function subscribe($events)
    {
        $events->listen(
            QuizStarted::class,
            [NotifyAdmin::class, 'handleQuizStarted']
        );

        $events->listen(
            QuizEnded::class,
            [NotifyAdmin::class, 'handleQuizEnded']
        );
    }
}