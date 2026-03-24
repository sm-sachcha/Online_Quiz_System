<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Events\TimerSynced;
use App\Events\QuizCountdown;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    public function syncTimer(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'time_remaining' => 'required|integer|min:0'
        ]);

        broadcast(new TimerSynced(
            $quiz,
            $request->question_id,
            $request->time_remaining
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    public function startCountdown(Request $request, Quiz $quiz)
    {
        $request->validate([
            'seconds' => 'required|integer|min:3|max:10'
        ]);

        broadcast(new QuizCountdown($quiz, $request->seconds))->toOthers();

        return response()->json(['success' => true]);
    }

    public function getQuestionTime(Question $question)
    {
        return response()->json([
            'question_id' => $question->id,
            'time_seconds' => $question->time_seconds
        ]);
    }
}