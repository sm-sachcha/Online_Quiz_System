<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\Question;
use App\Models\Option;
use App\Models\UserAnswer;
use App\Services\QuizService;
use App\Services\LeaderboardService;
use App\Events\AnswerSubmitted;
use App\Events\UserDisconnected;
use App\Events\ParticipantLeft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuizAttemptController extends Controller
{
    protected QuizService $quizService;
    protected LeaderboardService $leaderboardService;

    public function __construct(QuizService $quizService, LeaderboardService $leaderboardService)
    {
        $this->quizService = $quizService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Get quiz status for checking if started
     */
    public function getQuizStatus(Quiz $quiz)
    {
        $user = Auth::user();
        $hasCompleted = false;
        
        if ($user) {
            $hasCompleted = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->exists();
        }
        
        $hasInProgressAttempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->exists();
        
        $quizStartedByAdmin = $quiz->is_published && $quiz->scheduled_at && $quiz->scheduled_at <= now();
        $quizAlreadyStarted = $hasInProgressAttempts || $quizStartedByAdmin;
        
        return response()->json([
            'is_started' => $quizAlreadyStarted && !$hasCompleted,
            'has_completed' => $hasCompleted,
            'is_published' => $quiz->is_published,
            'scheduled_at' => $quiz->scheduled_at,
            'ends_at' => $quiz->ends_at,
            'has_questions' => $quiz->questions()->count() > 0,
            'total_questions' => $quiz->questions()->count(),
            'message' => $hasCompleted ? 'You have already completed this quiz.' : null
        ]);
    }

    public function start(Quiz $quiz)
    {
        try {
            Log::info('Quiz start called', ['quiz_id' => $quiz->id]);
            
            $requiresLogin = $quiz->category_id !== null;
            
            if ($requiresLogin && !Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to take this quiz.');
            }
            
            $user = Auth::user();
            $userId = $user ? $user->id : null;
            $participant = null;
            $guestName = null;
            
            // CHECK IF USER HAS ALREADY COMPLETED THE QUIZ
            if ($userId) {
                $hasCompleted = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->exists();
                
                if ($hasCompleted) {
                    return redirect()->route('user.dashboard')
                        ->with('error', 'You have already completed this quiz. You cannot start a new attempt.');
                }
            }
            
            // Handle guest user
            if (!$user && !$requiresLogin) {
                $guestResult = $this->handleGuestParticipant($quiz);
                if ($guestResult instanceof \Illuminate\Http\RedirectResponse) {
                    return $guestResult;
                }
                $participant = $guestResult;
                $guestName = session('guest_name');
            }
            
            // Check quiz availability
            $availabilityCheck = $this->checkQuizAvailability($quiz);
            if ($availabilityCheck) {
                return $availabilityCheck;
            }
            
            // Check for existing attempts
            $existingAttempt = $this->findExistingAttempt($quiz, $userId, $participant);
            if ($existingAttempt) {
                return $existingAttempt;
            }
            
            // Check max attempts for logged-in users
            if ($userId) {
                $maxAttemptsCheck = $this->checkMaxAttempts($quiz, $userId);
                if ($maxAttemptsCheck) {
                    return $maxAttemptsCheck;
                }
            }
            
            // Create new attempt
            $attempt = $this->quizService->startQuiz($quiz, $userId, $participant);
            
            // Update participant status
            $this->updateParticipantStatus($quiz, $userId, $participant, 'taking_quiz');
            
            Log::info('Quiz attempt created successfully', ['attempt_id' => $attempt->id]);
            
            return redirect()->route('user.quiz.attempt', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('success', 'Quiz started! Good luck!');
            
        } catch (\Exception $e) {
            Log::error('Start quiz error: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    private function handleGuestParticipant(Quiz $quiz)
    {
        $guestName = session('guest_name');
        
        if (!$guestName) {
            return redirect()->route('user.quiz.lobby', $quiz)
                ->with('error', 'Please join the lobby first by entering your name.');
        }
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('guest_name', $guestName)
            ->where('is_guest', true)
            ->first();
        
        if (!$participant) {
            $participant = QuizParticipant::create([
                'quiz_id' => $quiz->id,
                'guest_name' => $guestName,
                'is_guest' => true,
                'status' => 'joined',
                'joined_at' => now()
            ]);
        } else {
            $participant->update([
                'status' => 'joined',
                'joined_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return $participant;
    }

    private function checkQuizAvailability(Quiz $quiz)
    {
        $isQuizStarted = $quiz->is_published && $quiz->scheduled_at && $quiz->scheduled_at <= now();
        
        if (!$isQuizStarted) {
            return redirect()->route('user.quiz.lobby', $quiz)
                ->with('info', 'The quiz has not started yet. Please wait for the admin to start the quiz.');
        }
        
        if ($quiz->ends_at && $quiz->ends_at < now()) {
            return redirect()->route('user.quiz.lobby', $quiz)
                ->with('error', 'This quiz has already ended.');
        }
        
        return null;
    }

    private function findExistingAttempt(Quiz $quiz, $userId, $participant)
    {
        // Check for in-progress attempt
        $inProgressAttempt = null;
        if ($userId) {
            $inProgressAttempt = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'in_progress')
                ->first();
        } elseif ($participant) {
            $inProgressAttempt = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('participant_id', $participant->id)
                ->where('status', 'in_progress')
                ->first();
        }
        
        if ($inProgressAttempt) {
            return redirect()->route('user.quiz.attempt', [
                'quiz' => $quiz->id, 
                'attempt' => $inProgressAttempt->id
            ])->with('info', 'Resuming your previous attempt.');
        }
        
        // Check for abandoned attempt
        $abandonedAttempt = null;
        if ($userId) {
            $abandonedAttempt = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'abandoned')
                ->first();
        } elseif ($participant) {
            $abandonedAttempt = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('participant_id', $participant->id)
                ->where('status', 'abandoned')
                ->first();
        }
        
        if ($abandonedAttempt) {
            $abandonedAttempt->update([
                'status' => 'in_progress',
                'updated_at' => now()
            ]);
            
            return redirect()->route('user.quiz.attempt', [
                'quiz' => $quiz->id, 
                'attempt' => $abandonedAttempt->id
            ])->with('info', 'Resuming your previous attempt.');
        }
        
        return null;
    }

    private function checkMaxAttempts(Quiz $quiz, $userId)
    {
        $completedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();
        
        if ($completedAttempts >= $quiz->max_attempts && $quiz->max_attempts > 0) {
            return redirect()->route('user.dashboard')
                ->with('error', 'You have reached the maximum number of attempts (' . $quiz->max_attempts . ') for this quiz.');
        }
        
        return null;
    }

    public function attempt(Quiz $quiz, QuizAttempt $attempt)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        
        if ($userId && $attempt->user_id !== $userId) {
            abort(403);
        }
        
        if ($quiz->scheduled_at && $quiz->scheduled_at > now()) {
            return redirect()->route('user.quiz.lobby', $quiz)->with('error', 'Quiz has not started yet.');
        }
        
        // IF ATTEMPT IS COMPLETED - SHOW RESULTS PAGE
        if ($attempt->status === 'completed') {
            // Update participant status to completed
            $this->updateParticipantStatus($quiz, $userId, null, 'completed');
            QuizParticipant::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->update(['status' => 'completed', 'left_at' => now()]);
                
            return redirect()->route('user.quiz.result', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('info', 'This quiz has already been completed. View your results below.');
        }
        
        // Update participant timestamp
        $this->updateParticipantStatus($quiz, $userId, null, 'taking_quiz');
        
        if ($attempt->status === 'abandoned') {
            return redirect()->route('user.quiz.start', $quiz)
                ->with('info', 'Your previous attempt was abandoned. Starting a new attempt.');
        }
        
        // Check time expiry
        $timeExpiryCheck = $this->checkTimeExpiry($quiz, $attempt);
        if ($timeExpiryCheck) {
            return $timeExpiryCheck;
        }
        
        // Get next question
        $nextQuestionData = $this->getNextQuestion($quiz, $attempt);
        
        if ($nextQuestionData['is_completed']) {
            return $this->completeQuiz($quiz, $attempt);
        }
        
        return view('user.quiz.session', array_merge(
            compact('quiz', 'attempt'),
            $nextQuestionData
        ));
    }

    private function checkTimeExpiry(Quiz $quiz, QuizAttempt $attempt)
    {
        $timeLimit = $attempt->started_at->addMinutes($quiz->duration_minutes);
        
        if ($timeLimit < now()) {
            $this->autoSubmitRemainingQuestions($attempt, $quiz);
            
            $attempt->update([
                'status' => 'completed',
                'ended_at' => now()
            ]);
            
            $this->markParticipantLeft($quiz, $attempt);
            $this->leaderboardService->updateLeaderboard($quiz);
            
            return redirect()->route('user.quiz.result', [
                'quiz' => $quiz->id, 
                'attempt' => $attempt->id
            ])->with('error', 'Time has expired. Your answers have been submitted.');
        }
        
        return null;
    }

    private function getNextQuestion(Quiz $quiz, QuizAttempt $attempt)
    {
        $allQuestions = $quiz->questions()->orderBy('order')->get();
        
        if ($allQuestions->isEmpty()) {
            return ['is_completed' => true, 'error' => 'This quiz has no questions.'];
        }
        
        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();
        
        $currentQuestion = null;
        $answeredCount = 0;
        
        foreach ($allQuestions as $question) {
            if (!in_array($question->id, $answeredQuestionIds)) {
                $currentQuestion = $question;
                break;
            }
            $answeredCount++;
        }
        
        $totalQuestions = $allQuestions->count();
        $isCompleted = $currentQuestion === null;
        
        return [
            'currentQuestion' => $currentQuestion,
            'answeredCount' => $answeredCount,
            'totalQuestions' => $totalQuestions,
            'is_completed' => $isCompleted
        ];
    }

    private function completeQuiz(Quiz $quiz, QuizAttempt $attempt)
    {
        $attempt->update([
            'status' => 'completed',
            'ended_at' => now()
        ]);
        
        $this->markParticipantLeft($quiz, $attempt);
        $this->leaderboardService->updateLeaderboard($quiz);
        
        return redirect()->route('user.quiz.result', [
            'quiz' => $quiz->id, 
            'attempt' => $attempt->id
        ])->with('success', 'Quiz completed successfully!');
    }

    private function autoSubmitRemainingQuestions(QuizAttempt $attempt, Quiz $quiz)
    {
        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();
        $allQuestions = $quiz->questions()->orderBy('order')->get();
        
        foreach ($allQuestions as $question) {
            if (!in_array($question->id, $answeredQuestionIds)) {
                UserAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => null,
                    'answer_text' => null,
                    'is_correct' => false,
                    'points_earned' => 0,
                    'time_taken_seconds' => $question->time_seconds
                ]);
                
                $attempt->increment('incorrect_answers');
            }
        }
        
        $attempt->save();
    }

    public function submitAnswer(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'option_id' => 'required|exists:options,id',
            'time_taken' => 'required|integer|min:0',
            'question_type' => 'required|string'
        ]);

        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'error' => 'This attempt is already completed',
                'redirect_url' => route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
            ], 400);
        }

        // Check if already answered this question
        $existingAnswer = $attempt->answers()->where('question_id', $request->question_id)->first();
        if ($existingAnswer) {
            return response()->json(['error' => 'Question already answered'], 400);
        }

        try {
            $answer = $this->quizService->submitAnswer(
                $attempt,
                $request->question_id,
                $request->option_id,
                $request->time_taken
            );

            broadcast(new AnswerSubmitted($answer))->toOthers();

            $totalQuestions = $quiz->questions()->count();
            $answeredCount = $attempt->answers()->count();
            $isCompleted = $answeredCount >= $totalQuestions;
            
            if ($isCompleted) {
                $attempt->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);
                
                // IMPORTANT: Update participant status to 'completed' so they disappear from lobby
                QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', Auth::id())
                    ->update([
                        'status' => 'completed',
                        'left_at' => now()
                    ]);
                
                $this->leaderboardService->updateLeaderboard($quiz);
            } else {
                $attempt->touch();
            }

            $correctOption = null;
            if ($request->question_type !== 'multiple_choice') {
                $question = Question::with('options')->find($request->question_id);
                $correctOption = $question->options->where('is_correct', true)->first();
            }

            return response()->json([
                'success' => true,
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned,
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function submitMultipleAnswer(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_options' => 'required|json',
            'time_taken' => 'required|integer|min:0',
            'question_type' => 'required|string'
        ]);

        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'error' => 'This attempt is already completed',
                'redirect_url' => route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
            ], 400);
        }

        $existingAnswer = $attempt->answers()->where('question_id', $request->question_id)->first();
        if ($existingAnswer) {
            return response()->json(['error' => 'Question already answered'], 400);
        }

        try {
            $selectedOptions = json_decode($request->selected_options, true);
            $question = Question::with('options')->findOrFail($request->question_id);
            
            $correctOptions = $question->options->where('is_correct', true)->pluck('id')->toArray();
            
            sort($selectedOptions);
            sort($correctOptions);
            $isCorrect = ($selectedOptions == $correctOptions);
            $pointsEarned = $isCorrect ? $question->points : 0;
            
            $answer = UserAnswer::create([
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
            
            if ($isCompleted) {
                $attempt->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);
                
                // IMPORTANT: Update participant status to 'completed'
                QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', Auth::id())
                    ->update([
                        'status' => 'completed',
                        'left_at' => now()
                    ]);
                
                $this->leaderboardService->updateLeaderboard($quiz);
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function finish(Quiz $quiz, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
                ->with('info', 'This attempt is already completed.');
        }

        // Mark all remaining questions as incorrect
        $allQuestions = $quiz->questions()->orderBy('order')->get();
        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();
        
        foreach ($allQuestions as $question) {
            if (!in_array($question->id, $answeredQuestionIds)) {
                UserAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'option_id' => null,
                    'answer_text' => 'User finished quiz - No answer provided',
                    'is_correct' => false,
                    'points_earned' => 0,
                    'time_taken_seconds' => 0
                ]);
                $attempt->incorrect_answers++;
            }
        }
        $attempt->save();

        $attempt->update([
            'status' => 'completed',
            'ended_at' => now()
        ]);
        
        // IMPORTANT: Update participant status to 'completed'
        QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->update([
                'status' => 'completed',
                'left_at' => now()
            ]);
        
        $this->leaderboardService->updateLeaderboard($quiz);

        return redirect()->route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
            ->with('success', 'Quiz submitted successfully!');
    }

    private function updateParticipantStatus(Quiz $quiz, $userId, $participant, string $status)
    {
        if ($userId) {
            QuizParticipant::updateOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => $userId],
                ['status' => $status, 'updated_at' => now()]
            );
        } elseif ($participant) {
            $participant->update(['status' => $status, 'updated_at' => now()]);
        }
    }

    private function markParticipantLeft(Quiz $quiz, ?QuizAttempt $attempt = null)
    {
        $user = Auth::user();
        
        if ($user) {
            $participant = QuizParticipant::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->first();
            
            if ($participant && in_array($participant->status, ['joined', 'taking_quiz'])) {
                $participant->update(['status' => 'left', 'left_at' => now()]);
                broadcast(new ParticipantLeft($user, $quiz))->toOthers();
            }
        } elseif ($attempt && $attempt->participant_id) {
            $participant = QuizParticipant::where('id', $attempt->participant_id)->first();
            if ($participant && in_array($participant->status, ['joined', 'taking_quiz'])) {
                $participant->update(['status' => 'left', 'left_at' => now()]);
                
                $guestUser = new \stdClass();
                $guestUser->name = $participant->guest_name ?? 'Guest';
                broadcast(new ParticipantLeft($guestUser, $quiz))->toOthers();
            }
        }
    }

    public function heartbeat(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        
        if ($userId && $attempt->user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'Attempt not in progress'], 400);
        }
        
        $this->updateParticipantStatus($quiz, $userId, null, 'taking_quiz');
        $attempt->touch();
        
        return response()->json(['success' => true]);
    }

    public function leaveQuiz(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        
        if ($userId && $attempt->user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status === 'in_progress') {
            $attempt->update([
                'status' => 'abandoned',
                'ended_at' => now()
            ]);
            
            if ($userId) {
                QuizParticipant::updateOrCreate(
                    ['quiz_id' => $quiz->id, 'user_id' => $userId],
                    ['status' => 'left', 'left_at' => now()]
                );
                broadcast(new UserDisconnected($user, $quiz))->toOthers();
            } else {
                $participant = QuizParticipant::where('id', $attempt->participant_id)->first();
                if ($participant) {
                    $participant->update(['status' => 'left', 'left_at' => now()]);
                    
                    $guestUser = new \stdClass();
                    $guestUser->name = $participant->guest_name ?? 'Guest';
                    broadcast(new UserDisconnected($guestUser, $quiz))->toOthers();
                }
            }
        }
        
        return response()->json(['success' => true]);
    }

    public function attempts(Quiz $quiz)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to view your attempts.');
        }
        
        $attempts = QuizAttempt::where('user_id', $user->id)
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