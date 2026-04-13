<?php

namespace App\Events;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttemptQuestionBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Quiz $quiz,
        public QuizAttempt $attempt,
        public Question $question,
        public int $questionNumber,
        public int $totalQuestions
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('quiz.attempt.' . $this->attempt->id);
    }

    public function broadcastAs(): string
    {
        return 'attempt.question.broadcasted';
    }

    public function broadcastWith(): array
    {
        return [
            'quiz_id' => $this->quiz->id,
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'question_text' => $this->question->question_text,
            'question_type' => $this->question->question_type,
            'question_number' => $this->questionNumber,
            'total_questions' => $this->totalQuestions,
            'time_seconds' => (int) $this->question->time_seconds,
            'points' => (int) $this->question->points,
            'show_answer' => (bool) $this->question->show_answer,
            'options' => $this->question->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'text' => $option->option_text,
                    'is_correct' => (bool) $option->is_correct,
                ];
            })->values()->all(),
        ];
    }
}
