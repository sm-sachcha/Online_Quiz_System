<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Events\QuestionBroadcasted;
use App\Events\CurrentQuestionBroadcasted;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function setCurrentQuestion(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);
        
        $request->validate([
            'question_id' => 'nullable|exists:questions,id',
            'question_number' => 'required|integer',
            'total_questions' => 'required|integer'
        ]);

        $question = null;
        if ($request->question_id) {
            $question = Question::with('options')->findOrFail($request->question_id);
        }

        // Update quiz with current question
        $quiz->update([
            'current_question_id' => $request->question_id,
            'current_question_number' => $request->question_number,
            'current_question_started_at' => now()
        ]);

        Log::info('Current question set for synchronized quiz', [
            'quiz_id' => $quiz->id,
            'question_id' => $request->question_id,
            'question_number' => $request->question_number
        ]);

        // Broadcast to all participants
        broadcast(new CurrentQuestionBroadcasted(
            $quiz,
            $question,
            $request->question_number,
            $request->total_questions
        ));

        return response()->json(['success' => true]);
    }

    public function nextQuestion(Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        if (!$quiz->is_synchronized) {
            return response()->json(['error' => 'Quiz is not in synchronized mode'], 400);
        }

        $currentQuestionNumber = $quiz->current_question_number ?? 0;
        $totalQuestions = $quiz->questions()->count();
        $nextQuestionNumber = $currentQuestionNumber + 1;

        if ($nextQuestionNumber > $totalQuestions) {
            // Quiz finished
            $quiz->update([
                'current_question_id' => null,
                'current_question_number' => $totalQuestions + 1,
            ]);

            broadcast(new CurrentQuestionBroadcasted($quiz, null, $totalQuestions + 1, $totalQuestions));
            return response()->json(['success' => true, 'message' => 'Quiz finished']);
        }

        $nextQuestion = $quiz->questions()
            ->orderBy('order')
            ->offset($nextQuestionNumber - 1)
            ->first();

        if (!$nextQuestion) {
            return response()->json(['error' => 'Question not found'], 404);
        }

        $quiz->update([
            'current_question_id' => $nextQuestion->id,
            'current_question_number' => $nextQuestionNumber,
            'current_question_started_at' => now()
        ]);

        broadcast(new CurrentQuestionBroadcasted(
            $quiz,
            $nextQuestion,
            $nextQuestionNumber,
            $totalQuestions
        ));

        return response()->json(['success' => true]);
    }

    public function previousQuestion(Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        if (!$quiz->is_synchronized) {
            return response()->json(['error' => 'Quiz is not in synchronized mode'], 400);
        }

        $currentQuestionNumber = $quiz->current_question_number ?? 1;
        $previousQuestionNumber = $currentQuestionNumber - 1;

        if ($previousQuestionNumber < 1) {
            return response()->json(['error' => 'Cannot go before first question'], 400);
        }

        $previousQuestion = $quiz->questions()
            ->orderBy('order')
            ->offset($previousQuestionNumber - 1)
            ->first();

        if (!$previousQuestion) {
            return response()->json(['error' => 'Question not found'], 404);
        }

        $quiz->update([
            'current_question_id' => $previousQuestion->id,
            'current_question_number' => $previousQuestionNumber,
            'current_question_started_at' => now()
        ]);

        broadcast(new CurrentQuestionBroadcasted(
            $quiz,
            $previousQuestion,
            $previousQuestionNumber,
            $quiz->questions()->count()
        ));

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
            'is_synchronized' => $quiz->is_synchronized,
            'current_question' => $currentQuestion ? [
                'id' => $currentQuestion->id,
                'order' => $currentQuestion->order
            ] : null
        ]);
    }
}