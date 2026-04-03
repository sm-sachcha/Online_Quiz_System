<?php

namespace App\Listeners;

use App\Events\QuizEnded;
use App\Events\QuizStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdmin implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleQuizStarted(QuizStarted $event)
    {
        try {
            Log::info("Quiz '{$event->quiz->title}' has started.", [
                'quiz_id' => $event->quiz->id,
                'started_at' => $event->startTime
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyAdmin (quiz started) failed: ' . $e->getMessage());
        }
    }

    public function handleQuizEnded(QuizEnded $event)
    {
        try {
            Log::info("Quiz '{$event->quiz->title}' has ended.", [
                'quiz_id' => $event->quiz->id,
                'ended_at' => $event->endTime
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyAdmin (quiz ended) failed: ' . $e->getMessage());
        }
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