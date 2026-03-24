<?php

namespace App\Listeners;

use App\Events\QuizEnded;
use App\Models\QuizSchedule;
use App\Services\ResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $quiz = $event->quiz;
        
        QuizSchedule::where('quiz_id', $quiz->id)
            ->where('status', 'ongoing')
            ->update(['status' => 'completed']);
        
        $this->resultService->calculateFinalResults($quiz);
    }
}