<?php

namespace App\Listeners;

use App\Events\QuizStarted;
use App\Models\QuizSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleQuizStart implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(QuizStarted $event): void
    {
        $quiz = $event->quiz;
        
        QuizSchedule::where('quiz_id', $quiz->id)
            ->where('status', 'scheduled')
            ->update(['status' => 'ongoing']);
    }
}