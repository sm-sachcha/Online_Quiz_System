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
use App\Events\AttemptQuestionBroadcasted;
use App\Events\AttemptResultUpdated;
use App\Events\QuizParticipantsUpdated;
use App\Services\QuizParticipantsPayloadService;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class QuizAttemptController extends Controller
{
    private const PRE_QUESTION_COUNTDOWN_SECONDS = 5;
    private const DEVICE_DELAY_GRACE_SECONDS = 2;

    protected QuizService $quizService;
    protected LeaderboardService $leaderboardService;
    protected QuizParticipantsPayloadService $quizParticipantsPayloadService;
    protected ResultService $resultService;

    public function __construct(
        QuizService $quizService,
        LeaderboardService $leaderboardService,
        QuizParticipantsPayloadService $quizParticipantsPayloadService,
        ResultService $resultService
    )
    {
        $this->quizService = $quizService;
        $this->leaderboardService = $leaderboardService;
        $this->quizParticipantsPayloadService = $quizParticipantsPayloadService;
        $this->resultService = $resultService;
    }

    /**
     * Get quiz status for checking if started
     */
    public function getQuizStatus(Quiz $quiz)
    {
        $user = Auth::user();
        $hasCompleted = false;
        $completedAttempts = 0;
        
        if ($user) {
            $completedAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();
            $hasCompleted = $completedAttempts > 0 && $quiz->max_attempts > 0 && $completedAttempts >= $quiz->max_attempts;
        }
        
        $quizStartedByAdmin = $quiz->is_published
            && $quiz->scheduled_at
            && $quiz->scheduled_at <= now()
            && (!$quiz->ends_at || $quiz->ends_at > now());
        
        return response()->json([
            'is_started' => $quizStartedByAdmin && !$hasCompleted,
            'has_completed' => $hasCompleted,
            'is_published' => $quiz->is_published,
            'scheduled_at' => $quiz->scheduled_at,
            'ends_at' => $quiz->ends_at,
            'has_questions' => $quiz->questions()->count() > 0,
            'total_questions' => $quiz->questions()->count(),
            'message' => $hasCompleted ? 'You have already used all attempts for this quiz.' : null
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
            
            // CHECK IF USER HAS REACHED MAX ATTEMPTS
            if ($userId) {
                $completedAttempts = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->count();
                
                if ($quiz->max_attempts > 0 && $completedAttempts >= $quiz->max_attempts) {
                    return redirect()->route('user.dashboard')
                        ->with('error', 'You have reached (' . $quiz->max_attempts . ') for this quiz.');
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
            $this->broadcastParticipantsUpdated($quiz);
            
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
        $sessionId = session()->getId();
        
        if (!$guestName) {
            return redirect()->route('user.quiz.lobby', $quiz)
                ->with('error', 'Please join the lobby first by entering your name.');
        }
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('is_guest', true)
            ->where('session_id', $sessionId)
            ->first();
        
        if (!$participant) {
            $participant = QuizParticipant::create([
                'quiz_id' => $quiz->id,
                'session_id' => $sessionId,
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

        if (!$this->attemptBelongsToCurrentParticipant($quiz, $attempt)) {
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

        $this->syncAttemptProgressToLiveSchedule($quiz, $attempt);
        $attempt->refresh();
        
        // Get next question
        $nextQuestionData = $this->getNextQuestion($quiz, $attempt);
        
        if ($nextQuestionData['is_completed']) {
            return $this->completeQuiz($quiz, $attempt);
        }
        
        return response()
            ->view('user.quiz.session', array_merge(
                compact('quiz', 'attempt'),
                $nextQuestionData
            ))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function checkTimeExpiry(Quiz $quiz, QuizAttempt $attempt)
    {
        $timeLimit = $quiz->ends_at
            ?? ($quiz->scheduled_at
                ? $quiz->scheduled_at->copy()->addMinutes($quiz->duration_minutes)
                : $attempt->started_at->copy()->addMinutes($quiz->duration_minutes));
        
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
        $allQuestions = $this->loadOrderedQuestions($quiz, $attempt);
        
        if ($allQuestions->isEmpty()) {
            return ['is_completed' => true, 'error' => 'This quiz has no questions.'];
        }
        $totalQuestions = $allQuestions->count();
        $answeredCount = $attempt->answers()->count();
        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();

        $currentQuestion = null;

        foreach ($allQuestions as $question) {
            if (!in_array($question->id, $answeredQuestionIds, true)) {
                $currentQuestion = $question;
                break;
            }
        }

        if (!$currentQuestion) {
            return [
                'currentQuestion' => null,
                'currentQuestionNumber' => $totalQuestions,
                'remainingTimeSeconds' => 0,
                'answeredCount' => $answeredCount,
                'totalQuestions' => $totalQuestions,
                'is_completed' => true
            ];
        }

        $this->quizService->applyAttemptOptionSequence($attempt, $currentQuestion);

        return [
            'currentQuestion' => $currentQuestion,
            'currentQuestionNumber' => $answeredCount + 1,
            'remainingTimeSeconds' => (int) $currentQuestion->time_seconds,
            'questionTiming' => $this->buildQuestionTimingPayload(
                $attempt,
                $allQuestions,
                $answeredCount + 1
            ),
            'answeredCount' => $answeredCount,
            'totalQuestions' => $totalQuestions,
            'is_completed' => false
        ];
    }

    private function buildQuestionTimingPayload(
        QuizAttempt $attempt,
        Collection $orderedQuestions,
        int $questionNumber
    ): array {
        $questionIndex = max(0, $questionNumber - 1);
        $currentQuestion = $orderedQuestions->get($questionIndex);
        $sessionStartedAt = $attempt->started_at ? $attempt->started_at->copy() : now();

        $elapsedQuestionSeconds = $orderedQuestions
            ->take($questionIndex)
            ->sum(fn (Question $question) => (int) $question->time_seconds);

        $elapsedCountdownSeconds = $questionIndex * self::PRE_QUESTION_COUNTDOWN_SECONDS;

        $countdownStartAt = $sessionStartedAt
            ->copy()
            ->addSeconds($elapsedQuestionSeconds + $elapsedCountdownSeconds);

        $questionStartAt = $countdownStartAt
            ->copy()
            ->addSeconds(self::PRE_QUESTION_COUNTDOWN_SECONDS);

        $questionDurationSeconds = (int) ($currentQuestion?->time_seconds ?? 0);

        return [
            'server_now' => now()->toIso8601String(),
            'session_started_at' => $sessionStartedAt->toIso8601String(),
            'countdown_seconds' => self::PRE_QUESTION_COUNTDOWN_SECONDS,
            'delay_grace_seconds' => self::DEVICE_DELAY_GRACE_SECONDS,
            'countdown_start_at' => $countdownStartAt->toIso8601String(),
            'question_start_at' => $questionStartAt->toIso8601String(),
            'question_end_at' => $questionStartAt->copy()->addSeconds($questionDurationSeconds)->toIso8601String(),
        ];
    }

    private function syncAttemptProgressToLiveSchedule(Quiz $quiz, QuizAttempt $attempt): void
    {
        DB::transaction(function () use ($quiz, $attempt) {
            $lockedAttempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->first();

            if (!$lockedAttempt || $lockedAttempt->status !== 'in_progress') {
                return;
            }

            $orderedQuestions = $this->loadOrderedQuestions($quiz, $lockedAttempt, false);
            $answeredQuestionIds = UserAnswer::where('quiz_attempt_id', $lockedAttempt->id)
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $sessionStartedAt = $lockedAttempt->started_at ? $lockedAttempt->started_at->copy() : now();
            $cursor = $sessionStartedAt->copy();
            $missedQuestions = 0;

            foreach ($orderedQuestions as $question) {
                $questionStartAt = $cursor->copy()->addSeconds(self::PRE_QUESTION_COUNTDOWN_SECONDS);
                $questionEndAt = $questionStartAt->copy()->addSeconds((int) $question->time_seconds);

                if (!in_array((int) $question->id, $answeredQuestionIds, true)
                    && now()->greaterThan($questionEndAt->copy()->addSeconds(self::DEVICE_DELAY_GRACE_SECONDS))) {
                    UserAnswer::create([
                        'quiz_attempt_id' => $lockedAttempt->id,
                        'question_id' => $question->id,
                        'option_id' => null,
                        'answer_text' => null,
                        'is_correct' => false,
                        'points_earned' => 0,
                        'time_taken_seconds' => (int) $question->time_seconds,
                    ]);

                    $lockedAttempt->incorrect_answers++;
                    $missedQuestions++;
                }

                $cursor = $questionEndAt;
            }

            if ($missedQuestions > 0) {
                $lockedAttempt->save();
            }
        });
    }

    private function loadOrderedQuestions(Quiz $quiz, QuizAttempt $attempt, bool $withOptions = true): Collection
    {
        $questionSequence = collect(
            $attempt->question_sequence ?: $quiz->questions()->orderBy('order')->pluck('id')->all()
        );

        $query = Question::query()->where('quiz_id', $quiz->id)->whereIn('id', $questionSequence->all());

        if ($withOptions) {
            $query->with('options');
        }

        return $query
            ->get()
            ->sortBy(fn ($question) => $questionSequence->search($question->id))
            ->values();
    }

    private function completeQuiz(Quiz $quiz, QuizAttempt $attempt)
    {
        $attempt->update([
            'status' => 'completed',
            'ended_at' => now()
        ]);

        $this->clearGuestLobbyIdentityIfNeeded($attempt);
        
        $this->markParticipantLeft($quiz, $attempt);
        $this->leaderboardService->updateLeaderboard($quiz);
        $this->broadcastParticipantsUpdated($quiz);
        
        return redirect()->route('user.quiz.result', [
            'quiz' => $quiz->id, 
            'attempt' => $attempt->id
        ])->with('success', 'Quiz completed successfully!');
    }

    private function autoSubmitRemainingQuestions(QuizAttempt $attempt, Quiz $quiz)
    {
        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->toArray();
        $questionSequence = collect(
            $attempt->question_sequence ?: $quiz->questions()->orderBy('order')->pluck('id')->all()
        );
        $allQuestions = Question::where('quiz_id', $quiz->id)
            ->whereIn('id', $questionSequence->all())
            ->get()
            ->sortBy(fn ($question) => $questionSequence->search($question->id))
            ->values();
        
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
            'option_id' => 'nullable|exists:options,id',
            'time_taken' => 'required|integer|min:0',
            'question_type' => 'required|string'
        ]);

        if (!$this->attemptBelongsToCurrentParticipant($quiz, $attempt)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'error' => 'This attempt is already completed',
                'redirect_url' => route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
            ], 400);
        }

        try {
            $answer = $this->quizService->submitAnswer(
                $attempt,
                $request->question_id,
                $request->option_id,
                $request->time_taken
            );

            broadcast(new AnswerSubmitted($answer))->toOthers();

            $totalQuestions = (int) ($attempt->total_questions ?: $quiz->questions()->count());
            $answeredCount = $attempt->answers()->count();
            $isCompleted = $answeredCount >= $totalQuestions;
            
            if ($isCompleted) {
                $attempt->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);

                $this->clearGuestLobbyIdentityIfNeeded($attempt);
                
                // IMPORTANT: Update participant status to 'completed' so they disappear from lobby
                QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', Auth::id())
                    ->update([
                        'status' => 'completed',
                        'left_at' => now()
                    ]);
                
                $this->leaderboardService->updateLeaderboard($quiz);
                $this->broadcastParticipantsUpdated($quiz);
                $this->broadcastAttemptResultUpdated($quiz, $attempt);
            } else {
                $attempt->touch();
                $this->broadcastParticipantsUpdated($quiz);
                $this->broadcastNextAttemptQuestion($quiz, $attempt);
            }

            $nextQuestionPayload = $isCompleted
                ? null
                : $this->buildNextAttemptQuestionPayload($quiz, $attempt);

            $correctOption = null;
            if ($request->question_type !== 'multiple_choice') {
                $question = Question::with('options')->find($request->question_id);
                $this->quizService->applyAttemptOptionSequence($attempt, $question);
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
                'next_question' => $nextQuestionPayload,
                'message' => $isCompleted ? 'Quiz completed! Redirecting to results...' : 'Answer submitted successfully!'
            ]);
        } catch (\Exception $e) {
            if (in_array($e->getMessage(), ['Question already answered', 'This attempt is already completed'], true)) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'redirect_url' => $e->getMessage() === 'This attempt is already completed'
                        ? route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
                        : null,
                ], 400);
            }

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

        if (!$this->attemptBelongsToCurrentParticipant($quiz, $attempt)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'error' => 'This attempt is already completed',
                'redirect_url' => route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
            ], 400);
        }

        try {
            $selectedOptions = json_decode($request->selected_options, true);
            if (!is_array($selectedOptions)) {
                return response()->json(['error' => 'Invalid selected options'], 422);
            }

            $result = DB::transaction(function () use ($attempt, $request, $selectedOptions) {
                $lockedAttempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

                if ($lockedAttempt->status !== 'in_progress') {
                    return [
                        'type' => 'completed',
                        'redirect_url' => route('user.quiz.result', ['quiz' => $lockedAttempt->quiz_id, 'attempt' => $lockedAttempt->id]),
                    ];
                }

                $existingAnswer = UserAnswer::where('quiz_attempt_id', $lockedAttempt->id)
                    ->where('question_id', $request->question_id)
                    ->first();

                if ($existingAnswer) {
                    return ['type' => 'duplicate'];
                }

                $question = Question::with('options')
                    ->where('quiz_id', $lockedAttempt->quiz_id)
                    ->findOrFail($request->question_id);

                $correctOptions = $question->options->where('is_correct', true)->pluck('id')->toArray();
                $selectedSorted = $selectedOptions;
                $correctSorted = $correctOptions;
                sort($selectedSorted);
                sort($correctSorted);

                $isCorrect = ($selectedSorted == $correctSorted);
                $pointsEarned = $isCorrect ? $question->points : 0;

                $answer = UserAnswer::create([
                    'quiz_attempt_id' => $lockedAttempt->id,
                    'question_id' => $request->question_id,
                    'answer_text' => json_encode(array_values($selectedOptions)),
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'time_taken_seconds' => $request->time_taken
                ]);

                if ($isCorrect) {
                    $lockedAttempt->correct_answers++;
                } else {
                    $lockedAttempt->incorrect_answers++;
                }

                $lockedAttempt->score += $pointsEarned;
                $lockedAttempt->save();

                return [
                    'type' => 'success',
                    'answer' => $answer,
                    'attempt' => $lockedAttempt->fresh(),
                ];
            });

            if ($result['type'] === 'duplicate') {
                return response()->json(['error' => 'Question already answered'], 400);
            }

            if ($result['type'] === 'completed') {
                return response()->json([
                    'error' => 'This attempt is already completed',
                    'redirect_url' => $result['redirect_url'],
                ], 400);
            }

            $answer = $result['answer'];
            $attempt = $result['attempt'];
            
            broadcast(new AnswerSubmitted($answer))->toOthers();
            
            $totalQuestions = (int) ($attempt->total_questions ?: $quiz->questions()->count());
            $answeredCount = $attempt->answers()->count();
            $isCompleted = $answeredCount >= $totalQuestions;
            
            if ($isCompleted) {
                $attempt->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);

                $this->clearGuestLobbyIdentityIfNeeded($attempt);
                
                // IMPORTANT: Update participant status to 'completed'
                QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', Auth::id())
                    ->update([
                        'status' => 'completed',
                        'left_at' => now()
                    ]);
                
                $this->leaderboardService->updateLeaderboard($quiz);
                $this->broadcastParticipantsUpdated($quiz);
                $this->broadcastAttemptResultUpdated($quiz, $attempt);
            } else {
                $attempt->touch();
                $this->broadcastParticipantsUpdated($quiz);
                $this->broadcastNextAttemptQuestion($quiz, $attempt);
            }

            $nextQuestionPayload = $isCompleted
                ? null
                : $this->buildNextAttemptQuestionPayload($quiz, $attempt);
            
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
                'next_question' => $nextQuestionPayload,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function finish(Quiz $quiz, QuizAttempt $attempt)
    {
        if (!$this->attemptBelongsToCurrentParticipant($quiz, $attempt)) {
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

        $this->clearGuestLobbyIdentityIfNeeded($attempt);
        
        // IMPORTANT: Update participant status to 'completed'
        QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', Auth::id())
            ->update([
                'status' => 'completed',
                'left_at' => now()
            ]);
        
        $this->leaderboardService->updateLeaderboard($quiz);
        $this->broadcastParticipantsUpdated($quiz);

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
                $participant->loadMissing('user');
                broadcast(new ParticipantLeft($participant, $quiz))->toOthers();
                $this->broadcastParticipantsUpdated($quiz);
            }
        } elseif ($attempt && $attempt->participant_id) {
            $participant = QuizParticipant::where('id', $attempt->participant_id)->first();
            if ($participant && in_array($participant->status, ['joined', 'taking_quiz'])) {
                $participant->update(['status' => 'left', 'left_at' => now()]);

                broadcast(new ParticipantLeft($participant, $quiz))->toOthers();
                $this->broadcastParticipantsUpdated($quiz);
            }
        }
    }

    public function heartbeat(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;

        if (!$this->attemptBelongsToCurrentParticipant($quiz, $attempt)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'Attempt not in progress'], 400);
        }
        
        $this->updateParticipantStatus($quiz, $userId, null, 'taking_quiz');
        $attempt->touch();
        $this->broadcastParticipantsUpdated($quiz);
        
        return response()->json(['success' => true]);
    }

    public function leaveQuiz(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        
        if (!$this->attemptBelongsToCurrentParticipant($quiz, $attempt)) {
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
                $this->broadcastParticipantsUpdated($quiz);
            } else {
                $participant = QuizParticipant::where('id', $attempt->participant_id)->first();
                if ($participant) {
                    $participant->update(['status' => 'left', 'left_at' => now()]);
                    
                    $guestUser = new \stdClass();
                    $guestUser->name = $participant->guest_name ?? 'Guest';
                    broadcast(new UserDisconnected($guestUser, $quiz))->toOthers();
                    $this->broadcastParticipantsUpdated($quiz);
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

    private function findGuestParticipantBySession(Quiz $quiz): ?QuizParticipant
    {
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('is_guest', true)
            ->where('session_id', session()->getId())
            ->first();

        if ($participant) {
            return $participant;
        }

        $guestName = session('guest_name');
        if (!$guestName) {
            return null;
        }

        return QuizParticipant::where('quiz_id', $quiz->id)
            ->where('is_guest', true)
            ->whereNull('session_id')
            ->where('guest_name', $guestName)
            ->first();
    }

    private function clearGuestLobbyIdentityIfNeeded(QuizAttempt $attempt): void
    {
        if ($attempt->user_id !== null || !$attempt->participant_id) {
            return;
        }

        session([
            'guest_participant_id' => $attempt->participant_id,
        ]);

        session()->forget('guest_name');
    }

    private function broadcastParticipantsUpdated(Quiz $quiz): void
    {
        broadcast(new QuizParticipantsUpdated(
            $quiz,
            $this->quizParticipantsPayloadService->build($quiz)
        ))->toOthers();
    }

    private function broadcastNextAttemptQuestion(Quiz $quiz, QuizAttempt $attempt): void
    {
        $nextQuestionData = $this->getNextQuestion($quiz, $attempt);

        if (($nextQuestionData['is_completed'] ?? false) || empty($nextQuestionData['currentQuestion'])) {
            return;
        }

        broadcast(new AttemptQuestionBroadcasted(
            $quiz,
            $attempt,
            $nextQuestionData['currentQuestion'],
            (int) $nextQuestionData['currentQuestionNumber'],
            (int) $nextQuestionData['totalQuestions'],
            $nextQuestionData['questionTiming'] ?? []
        ))->toOthers();
    }

    private function buildNextAttemptQuestionPayload(Quiz $quiz, QuizAttempt $attempt): ?array
    {
        $nextQuestionData = $this->getNextQuestion($quiz, $attempt);

        if (($nextQuestionData['is_completed'] ?? false) || empty($nextQuestionData['currentQuestion'])) {
            return null;
        }

        return $this->transformQuestionForAttemptBroadcast(
            $nextQuestionData['currentQuestion'],
            (int) $nextQuestionData['currentQuestionNumber'],
            (int) $nextQuestionData['totalQuestions'],
            $nextQuestionData['questionTiming'] ?? []
        );
    }

    private function transformQuestionForAttemptBroadcast(
        Question $question,
        int $questionNumber,
        int $totalQuestions,
        array $timing = []
    ): array {
        return [
            'question_id' => $question->id,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'question_number' => $questionNumber,
            'total_questions' => $totalQuestions,
            'time_seconds' => (int) $question->time_seconds,
            'points' => (int) $question->points,
            'show_answer' => (bool) $question->show_answer,
            'timing' => $timing,
            'options' => $question->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'text' => $option->option_text,
                    'is_correct' => (bool) $option->is_correct,
                ];
            })->values()->all(),
        ];
    }

    private function broadcastAttemptResultUpdated(Quiz $quiz, QuizAttempt $attempt): void
    {
        $result = $attempt->result ?: $this->resultService->calculateResult($attempt);
        $leaderboard = $this->leaderboardService->getLeaderboard($quiz);
        $userRank = null;

        if ($attempt->user_id !== null) {
            $userRank = $leaderboard->firstWhere('user_id', $attempt->user_id)['rank'] ?? null;
        } elseif ($attempt->participant_id !== null) {
            $userRank = $leaderboard->firstWhere('participant_id', $attempt->participant_id)['rank'] ?? null;
        }

        $percentage = $quiz->total_points > 0
            ? round(($attempt->score / $quiz->total_points) * 100, 1)
            : 0;

        broadcast(new AttemptResultUpdated($attempt, [
            'redirect_url' => route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id]),
            'score' => $attempt->score,
            'correct_answers' => $attempt->correct_answers,
            'incorrect_answers' => $attempt->incorrect_answers,
            'percentage' => $percentage,
            'passed' => (bool) $result->passed,
            'rank' => $userRank,
            'total_participants' => $leaderboard->count(),
        ]))->toOthers();
    }

    private function attemptBelongsToCurrentParticipant(Quiz $quiz, QuizAttempt $attempt): bool
    {
        $userId = Auth::id();
        if ($userId) {
            return (int) $attempt->user_id === (int) $userId;
        }

        if (!$attempt->participant_id) {
            return false;
        }

        $participant = $this->findGuestParticipantBySession($quiz);

        return $participant && (int) $participant->id === (int) $attempt->participant_id;
    }
}
