<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Events\QuestionBroadcasted;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizBroadcastController extends Controller
{
    public function broadcastQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_id' => 'required|integer',
            'question_number' => 'required|integer|min:1',
            'total_questions' => 'required|integer|min:1'
        ]);

        if ($request->question_number > $request->total_questions) {
            return response()->json([
                'success' => false,
                'message' => 'The provided question number is invalid.'
            ], 422);
        }

        $question = $quiz->questions()
            ->with('options')
            ->findOrFail($request->question_id);

        DB::transaction(function () use ($quiz, $question) {
            $quiz->questions()->where('is_active', true)->update(['is_active' => false]);
            $question->update(['is_active' => true]);
        });

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
        $quiz->questions()->where('is_active', true)->update(['is_active' => false]);
        broadcast(new QuizStarted($quiz))->toOthers();
        return response()->json(['success' => true]);
    }

    public function endQuiz(Quiz $quiz)
    {
        $quiz->questions()->where('is_active', true)->update(['is_active' => false]);
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
                'order' => $currentQuestion->order,
                'question_text' => $currentQuestion->question_text
            ] : null
        ]);
    }
}