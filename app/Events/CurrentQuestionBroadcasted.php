<?php

namespace App\Events;

use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrentQuestionBroadcasted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $question;
    public $questionNumber;
    public $totalQuestions;
    public $timeSeconds;

    public function __construct(Quiz $quiz, ?Question $question = null, $questionNumber = 0, $totalQuestions = 0)
    {
        $this->quiz = $quiz;
        $this->question = $question;
        $this->questionNumber = $questionNumber;
        $this->totalQuestions = $totalQuestions;
        $this->timeSeconds = $question ? $question->time_seconds : 0;
    }

    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    public function broadcastAs()
    {
        return 'current.question.broadcasted';
    }

    public function broadcastWith()
    {
        if (!$this->question) {
            return [
                'quiz_id' => $this->quiz->id,
                'question_id' => null,
                'question_text' => null,
                'question_type' => null,
                'options' => [],
                'question_number' => 0,
                'total_questions' => $this->totalQuestions,
                'time_seconds' => 0,
                'points' => 0,
                'is_synchronized' => true,
                'message' => 'Quiz ended'
            ];
        }

        $options = $this->question->options->map(function ($option) {
            return [
                'id' => $option->id,
                'text' => $option->option_text,
            ];
        });

        return [
            'quiz_id' => $this->quiz->id,
            'question_id' => $this->question->id,
            'question_text' => $this->question->question_text,
            'question_type' => $this->question->question_type,
            'options' => $options,
            'question_number' => $this->questionNumber,
            'total_questions' => $this->totalQuestions,
            'time_seconds' => $this->timeSeconds,
            'points' => $this->question->points,
            'is_synchronized' => true,
        ];
    }
}
