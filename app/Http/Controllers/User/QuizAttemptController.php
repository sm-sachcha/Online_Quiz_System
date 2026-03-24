<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use App\Events\AnswerSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizAttemptController extends Controller
{
    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    public function start(Quiz $quiz)
    {
        try {
            $attempt = $this->quizService->startQuiz($quiz, Auth::id());
            return redirect()->route('user.quiz.attempt', ['quiz' => $quiz->id, 'attempt' => $attempt->id]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function attempt(Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id() || $attempt->quiz_id !== $quiz->id) {
            abort(403);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id]);
        }

        $currentQuestion = $this->quizService->getNextQuestion($quiz, $attempt);
        
        if (!$currentQuestion) {
            $attempt->update([
                'status' => 'completed',
                'ended_at' => now()
            ]);
            
            return redirect()->route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id]);
        }

        $answeredCount = $attempt->answers()->count();
        $totalQuestions = $quiz->questions->count();

        return view('user.quiz.session', compact('quiz', 'attempt', 'currentQuestion', 'answeredCount', 'totalQuestions'));
    }

    public function submitAnswer(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'option_id' => 'required|exists:options,id',
            'time_taken' => 'required|integer|min:0'
        ]);

        if ($attempt->user_id !== Auth::id() || $attempt->status !== 'in_progress') {
            return response()->json(['error' => 'Invalid attempt'], 403);
        }

        $answer = $this->quizService->submitAnswer(
            $attempt,
            $request->question_id,
            $request->option_id,
            $request->time_taken
        );

        broadcast(new AnswerSubmitted($answer))->toOthers();

        return response()->json([
            'success' => true,
            'is_correct' => $answer->is_correct,
            'points_earned' => $answer->points_earned,
            'current_score' => $attempt->score
        ]);
    }

    public function finish(Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $attempt->update([
            'status' => 'completed',
            'ended_at' => now()
        ]);

        return redirect()->route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id]);
    }

    public function attempts(Quiz $quiz)
    {
        // Get all attempts for this user on this quiz
        $attempts = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->orderByDesc('created_at')
            ->get();
        
        $remainingAttempts = $quiz->max_attempts - $attempts->where('status', 'completed')->count();
        
        return view('user.quiz.attempts', compact('quiz', 'attempts', 'remainingAttempts'));
    }
}