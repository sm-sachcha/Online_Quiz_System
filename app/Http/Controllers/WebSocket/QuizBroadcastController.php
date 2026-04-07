<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Events\QuestionBroadcasted;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use Illuminate\Http\Request;

class QuizBroadcastController extends Controller
{
    public function broadcastQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'question_number' => 'required|integer',
            'total_questions' => 'required|integer'
        ]);

        $question = Question::with('options')->findOrFail($request->question_id);

        broadcast(new QuestionBroadcasted(
            $quiz,
            $question,
            $request->question_number,
            $request->total_questions
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    public function startQuiz(Quiz $quiz)
    {
        broadcast(new QuizStarted($quiz))->toOthers();
        return response()->json(['success' => true]);
    }

    public function endQuiz(Quiz $quiz)
    {
        broadcast(new QuizEnded($quiz))->toOthers();
        return response()->json(['success' => true]);
    }

    public function getQuizStatus(Quiz $quiz)
    {
        $participantsCount = $quiz->participants()->where('status', 'joined')->count();
        $currentQuestion = $quiz->questions()
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        return response()->json([
            'quiz_id' => $quiz->id,
            'status' => $quiz->is_published ? 'active' : 'inactive',
            'participants_count' => $participantsCount,
            'current_question' => $currentQuestion ? [
                'id' => $currentQuestion->id,
                'order' => $currentQuestion->order
            ] : null
        ]);
    }
}