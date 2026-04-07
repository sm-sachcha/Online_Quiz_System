<?php

namespace App\Listeners;

use App\Events\QuizStarted;
use App\Models\QuizSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleQuizStart implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(QuizStarted $event): void
    {
        try {
            $quiz = $event->quiz;
            
            $schedule = QuizSchedule::where('quiz_id', $quiz->id)
                ->where('status', 'scheduled')
                ->first();
            
            if ($schedule) {
                $schedule->update(['status' => 'ongoing']);
                Log::info('Quiz schedule updated to ongoing for quiz: ' . $quiz->id);
            }
        } catch (\Exception $e) {
            Log::error('HandleQuizStart failed: ' . $e->getMessage());
        }
    }
}