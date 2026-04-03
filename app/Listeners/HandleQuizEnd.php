<?php

namespace App\Listeners;

use App\Events\QuizEnded;
use App\Models\QuizSchedule;
use App\Services\ResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleQuizEnd implements ShouldQueue
{
    use InteractsWithQueue;

    protected $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    public function handle(QuizEnded $event): void
    {
        try {
            $quiz = $event->quiz;
            
            $schedule = QuizSchedule::where('quiz_id', $quiz->id)
                ->where('status', 'ongoing')
                ->first();
            
            if ($schedule) {
                $schedule->update(['status' => 'completed']);
                Log::info('Quiz schedule updated to completed for quiz: ' . $quiz->id);
            }
            
            $this->resultService->calculateFinalResults($quiz);
            Log::info('Final results calculated for quiz: ' . $quiz->id);
        } catch (\Exception $e) {
            Log::error('HandleQuizEnd failed: ' . $e->getMessage());
        }
    }
}