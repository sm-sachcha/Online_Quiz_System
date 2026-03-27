<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Services\QuizService;
use App\Events\AnswerSubmitted;
use App\Events\UserDisconnected;
use App\Events\ParticipantLeft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            $user = Auth::user();
            
            // Check if user has an abandoned attempt
            $abandonedAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'abandoned')
                ->first();
            
            if ($abandonedAttempt) {
                $abandonedAttempt->update([
                    'status' => 'in_progress',
                    'updated_at' => now()
                ]);
                
                QuizParticipant::updateOrCreate(
                    ['quiz_id' => $quiz->id, 'user_id' => $user->id],
                    ['status' => 'taking_quiz', 'joined_at' => now(), 'updated_at' => now()]
                );
                
                return redirect()->route('user.quiz.attempt', [
                    'quiz' => $quiz->id, 
                    'attempt' => $abandonedAttempt->id
                ])->with('info', 'Resuming your previous attempt.');
            }
            
            // Check max attempts
            $completedAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();
            
            if ($completedAttempts >= $quiz->max_attempts) {
                return redirect()->route('user.dashboard')
                    ->with('error', 'You have reached the maximum number of attempts.');
            }
            
            // Check for existing in-progress attempt
            $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'in_progress')
                ->first();
            
            if ($inProgressAttempt) {
                return redirect()->route('user.quiz.attempt', [
                    'quiz' => $quiz->id, 
                    'attempt' => $inProgressAttempt->id
                ])->with('info', 'Resuming your previous attempt.');
            }
            
            // Create new attempt
            $attempt = $this->quizService->startQuiz($quiz, $user->id);
            
            QuizParticipant::updateOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => $user->id],
                ['status' => 'taking_quiz', 'joined_at' => now(), 'updated_at' => now()]
            );
            
            return redirect()->route('user.quiz.attempt', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('success', 'New attempt started! Good luck!');
            
        } catch (\Exception $e) {
            Log::error('Start quiz error: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    public function attempt(Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id() || $attempt->quiz_id !== $quiz->id) {
            abort(403);
        }
        
        QuizParticipant::updateOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => Auth::id()],
            ['status' => 'taking_quiz', 'updated_at' => now()]
        );

        if ($attempt->status === 'completed') {
            $this->markParticipantLeft($quiz);
            return redirect()->route('user.quiz.result', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('info', 'This attempt is already completed.');
        }
        
        if ($attempt->status === 'abandoned') {
            $completedAttempts = QuizAttempt::where('user_id', Auth::id())
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();
            
            if ($completedAttempts < $quiz->max_attempts) {
                return redirect()->route('user.quiz.start', $quiz)
                    ->with('info', 'Starting a new attempt.');
            } else {
                return redirect()->route('user.dashboard')
                    ->with('error', 'You have reached the maximum number of attempts.');
            }
        }

        // Check time expiry
        $timeLimit = $attempt->started_at->addMinutes($quiz->duration_minutes);
        if ($timeLimit < now()) {
            $this->autoSubmitRemainingQuestions($attempt, $quiz);
            
            $attempt->update([
                'status' => 'completed',
                'ended_at' => now()
            ]);
            
            $this->markParticipantLeft($quiz);
            
            $leaderboardService = app(\App\Services\LeaderboardService::class);
            $leaderboardService->updateLeaderboard($quiz);
            
            return redirect()->route('user.quiz.result', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('error', 'Time has expired. Your answers have been submitted.');
        }

        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();
        $allQuestions = $quiz->questions()->orderBy('order')->get();
        
        if ($allQuestions->isEmpty()) {
            return redirect()->route('user.quiz.lobby', $quiz)
                ->with('error', 'This quiz has no questions.');
        }
        
        $currentQuestion = null;
        $answeredCount = 0;
        
        foreach ($allQuestions as $question) {
            if (!in_array($question->id, $answeredQuestionIds)) {
                $currentQuestion = $question;
                break;
            }
            $answeredCount++;
        }
        
        if (!$currentQuestion) {
            $attempt->update([
                'status' => 'completed',
                'ended_at' => now()
            ]);
            
            $this->markParticipantLeft($quiz);
            
            $leaderboardService = app(\App\Services\LeaderboardService::class);
            $leaderboardService->updateLeaderboard($quiz);
            
            return redirect()->route('user.quiz.result', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('success', 'Quiz completed successfully!');
        }

        $totalQuestions = $allQuestions->count();

        return view('user.quiz.session', compact(
            'quiz', 
            'attempt', 
            'currentQuestion', 
            'answeredCount', 
            'totalQuestions'
        ));
    }

    public function submitAnswer(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'option_id' => 'nullable|exists:options,id',
            'time_taken' => 'required|integer|min:0'
        ]);

        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'This attempt is already completed'], 400);
        }

        $existingAnswer = $attempt->answers()->where('question_id', $request->question_id)->first();
        if ($existingAnswer) {
            return response()->json(['error' => 'Question already answered'], 400);
        }

        try {
            $question = \App\Models\Question::findOrFail($request->question_id);
            
            $isCorrect = false;
            $pointsEarned = 0;
            
            if ($request->option_id) {
                $selectedOption = \App\Models\Option::find($request->option_id);
                if ($selectedOption) {
                    $isCorrect = $selectedOption->is_correct;
                    $pointsEarned = $isCorrect ? $question->points : 0;
                }
            }
            
            $answer = \App\Models\UserAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $request->question_id,
                'option_id' => $request->option_id,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'time_taken_seconds' => $request->time_taken
            ]);
            
            $attempt->score += $pointsEarned;
            
            if ($isCorrect) {
                $attempt->correct_answers++;
            } else {
                $attempt->incorrect_answers++;
            }
            
            $attempt->save();
            
            broadcast(new AnswerSubmitted($answer))->toOthers();
            
            $totalQuestions = $quiz->questions()->count();
            $answeredCount = $attempt->answers()->count();
            $isCompleted = $answeredCount >= $totalQuestions;
            
            QuizParticipant::updateOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => Auth::id()],
                ['status' => 'taking_quiz', 'updated_at' => now()]
            );
            
            if ($isCompleted) {
                $attempt->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);
                
                $this->markParticipantLeft($quiz);
                
                $leaderboardService = app(\App\Services\LeaderboardService::class);
                $leaderboardService->updateLeaderboard($quiz);
            } else {
                $attempt->touch();
            }
            
            $correctOption = $question->options()->where('is_correct', true)->first();
            
            return response()->json([
                'success' => true,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'current_score' => $attempt->score,
                'is_completed' => $isCompleted,
                'answered_count' => $answeredCount,
                'total_questions' => $totalQuestions,
                'correct_answers' => $attempt->correct_answers,
                'incorrect_answers' => $attempt->incorrect_answers,
                'correct_option_id' => $correctOption ? $correctOption->id : null,
                'selected_option_id' => $request->option_id,
                'message' => $isCompleted ? 'Quiz completed! Redirecting to results...' : 'Answer submitted successfully!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Submit answer error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function submitMultipleAnswer(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_options' => 'required|json',
            'time_taken' => 'required|integer|min:0',
        ]);

        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'This attempt is already completed'], 400);
        }

        $existingAnswer = $attempt->answers()->where('question_id', $request->question_id)->first();
        if ($existingAnswer) {
            return response()->json(['error' => 'Question already answered'], 400);
        }

        try {
            $selectedOptions = json_decode($request->selected_options, true);
            $question = \App\Models\Question::with('options')->findOrFail($request->question_id);
            
            $correctOptions = $question->options->where('is_correct', true)->pluck('id')->toArray();
            
            sort($selectedOptions);
            sort($correctOptions);
            $isCorrect = ($selectedOptions == $correctOptions);
            $pointsEarned = $isCorrect ? $question->points : 0;
            
            $answer = \App\Models\UserAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $request->question_id,
                'answer_text' => json_encode($selectedOptions),
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'time_taken_seconds' => $request->time_taken
            ]);
            
            $attempt->score += $pointsEarned;
            if ($isCorrect) {
                $attempt->correct_answers++;
            } else {
                $attempt->incorrect_answers++;
            }
            $attempt->save();
            
            broadcast(new AnswerSubmitted($answer))->toOthers();
            
            $totalQuestions = $quiz->questions()->count();
            $answeredCount = $attempt->answers()->count();
            $isCompleted = $answeredCount >= $totalQuestions;
            
            QuizParticipant::updateOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => Auth::id()],
                ['status' => 'taking_quiz', 'updated_at' => now()]
            );
            
            if ($isCompleted) {
                $attempt->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);
                
                $this->markParticipantLeft($quiz);
                
                $leaderboardService = app(\App\Services\LeaderboardService::class);
                $leaderboardService->updateLeaderboard($quiz);
            } else {
                $attempt->touch();
            }
            
            return response()->json([
                'success' => true,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'current_score' => $attempt->score,
                'is_completed' => $isCompleted,
                'answered_count' => $answeredCount,
                'total_questions' => $totalQuestions,
                'correct_answers' => $attempt->correct_answers,
                'incorrect_answers' => $attempt->incorrect_answers
            ]);
        } catch (\Exception $e) {
            Log::error('Submit multiple answer error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function finish(Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('user.quiz.result', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('info', 'This attempt is already completed.');
        }

        $attempt->update([
            'status' => 'completed',
            'ended_at' => now()
        ]);
        
        $this->markParticipantLeft($quiz);
        
        $leaderboardService = app(\App\Services\LeaderboardService::class);
        $leaderboardService->updateLeaderboard($quiz);
        
        return redirect()->route('user.quiz.result', [
            'quiz' => $quiz->id, 
            'attempt' => $attempt->id
        ])->with('success', 'Quiz submitted successfully!');
    }

    private function markParticipantLeft($quiz)
    {
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->first();
        
        if ($participant && in_array($participant->status, ['joined', 'taking_quiz'])) {
            $participant->update([
                'status' => 'left',
                'left_at' => now()
            ]);
            
            broadcast(new ParticipantLeft(Auth::user(), $quiz))->toOthers();
        }
    }

    private function autoSubmitRemainingQuestions($attempt, $quiz)
    {
        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();
        $allQuestions = $quiz->questions()->orderBy('order')->get();
        
        foreach ($allQuestions as $question) {
            if (!in_array($question->id, $answeredQuestionIds)) {
                \App\Models\UserAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => null,
                    'is_correct' => false,
                    'points_earned' => 0,
                    'time_taken_seconds' => $question->time_seconds
                ]);
                
                $attempt->incorrect_answers++;
            }
        }
        
        $attempt->save();
    }

    public function heartbeat(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'Attempt not in progress'], 400);
        }
        
        QuizParticipant::updateOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => Auth::id()],
            ['status' => 'taking_quiz', 'updated_at' => now()]
        );
        
        $attempt->touch();
        
        return response()->json(['success' => true]);
    }

    public function leaveQuiz(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status === 'in_progress') {
            $attempt->update([
                'status' => 'abandoned',
                'ended_at' => now()
            ]);
            
            QuizParticipant::updateOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => Auth::id()],
                ['status' => 'left', 'left_at' => now()]
            );
            
            broadcast(new UserDisconnected(Auth::user(), $quiz))->toOthers();
        }
        
        return response()->json(['success' => true]);
    }

    public function attempts(Quiz $quiz)
    {
        $attempts = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->orderByDesc('created_at')
            ->get();
        
        $completedAttempts = $attempts->where('status', 'completed')->count();
        $remainingAttempts = max(0, $quiz->max_attempts - $completedAttempts);
        $inProgressAttempt = $attempts->where('status', 'in_progress')->first();
        $abandonedAttempt = $attempts->where('status', 'abandoned')->first();
        
        return view('user.quiz.attempts', compact(
            'quiz', 
            'attempts', 
            'remainingAttempts',
            'completedAttempts',
            'inProgressAttempt',
            'abandonedAttempt'
        ));
    }
}