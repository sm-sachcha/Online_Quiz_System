<?php

namespace App\Listeners;

use App\Events\AnswerSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateQuizStatistics implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AnswerSubmitted $event): void
    {
        $quizAttempt = $event->answer->quizAttempt;
        $user = $quizAttempt->user;
        $profile = $user->profile;
        
        if ($profile) {
            $profile->increment('total_points', $event->answer->points_earned);
            $profile->increment('quizzes_attempted');
            
            if ($quizAttempt->result && $quizAttempt->result->passed) {
                $profile->increment('quizzes_won');
            }
            
            $profile->save();
        }
    }
}