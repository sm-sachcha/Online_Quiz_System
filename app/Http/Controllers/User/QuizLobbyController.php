<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use App\Models\QuizAttempt;
use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
use App\Events\QuizParticipantsUpdated;
use App\Services\QuizParticipantsPayloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuizLobbyController extends Controller
{
    public function __construct(
        private QuizParticipantsPayloadService $quizParticipantsPayloadService
    ) {
    }

    public function index(Quiz $quiz)
    {
        if (!$quiz->is_published) {
            abort(404);
        }

        // Check if quiz requires login (has category)
        $requiresLogin = $quiz->category_id !== null;
        
        // If quiz requires login and user is not authenticated, redirect to login
        if ($requiresLogin && !Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this quiz.');
        }

        $user = Auth::user();
        
        // CHECK IF QUIZ HAS ENDED - Time is over
        $quizEnded = $quiz->ends_at && $quiz->ends_at < now();
        
        // CHECK IF USER HAS REACHED MAX ATTEMPTS
        $hasCompleted = false;
        $completedAttempt = null;
        
        if ($user) {
            $completedAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->latest()
                ->first();
            $completedAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();
            $hasCompleted = $quiz->max_attempts > 0 && $completedAttempts >= $quiz->max_attempts;
        } else {
            $guestName = session('guest_name');
            if ($guestName) {
                $participant = $this->findGuestParticipantBySession($quiz);
                if ($participant) {
                    $completedAttempt = QuizAttempt::where('participant_id', $participant->id)
                        ->where('status', 'completed')
                        ->latest()
                        ->first();
                    $completedAttempts = QuizAttempt::where('participant_id', $participant->id)
                        ->where('quiz_id', $quiz->id)
                        ->where('status', 'completed')
                        ->count();
                    $hasCompleted = $quiz->max_attempts > 0 && $completedAttempts >= $quiz->max_attempts;
                }
            }
        }
        
        $quizStartedByAdmin = $quiz->is_published
            && $quiz->scheduled_at
            && $quiz->scheduled_at <= now()
            && !$quizEnded;
        $quizAlreadyStarted = $quizStartedByAdmin;
        
        // Check if user already has an in-progress attempt (to allow resuming)
        $userHasInProgress = false;
        $inProgressAttempt = null;
        
        if ($user) {
            $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'in_progress')
                ->first();
            $userHasInProgress = $inProgressAttempt ? true : false;
        } else {
            $guestName = session('guest_name');
            if ($guestName) {
                $participant = $this->findGuestParticipantBySession($quiz);
                if ($participant) {
                    $inProgressAttempt = QuizAttempt::where('quiz_id', $quiz->id)
                        ->where('participant_id', $participant->id)
                        ->where('status', 'in_progress')
                        ->first();
                    $userHasInProgress = $inProgressAttempt ? true : false;
                }
            }
        }
        
        // Check remaining attempts
        $completedAttempts = 0;
        $remainingAttempts = $quiz->max_attempts;
        $abandonedAttempt = null;
        $participant = null;
        
        if ($user) {
            $completedAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();
            
            $remainingAttempts = max(0, $quiz->max_attempts - $completedAttempts);
            
            $abandonedAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'abandoned')
                ->first();
            
            // Only allow participant creation if:
            // 1. User has an in-progress attempt (resume), OR
            // 2. Quiz hasn't started AND user has remaining attempts AND user hasn't completed AND quiz hasn't ended
            if (!$hasCompleted && !$quizEnded && ($userHasInProgress || $remainingAttempts > 0 || $quiz->max_attempts <= 0)) {
                $participant = QuizParticipant::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'user_id' => $user->id],
                    ['status' => 'joined', 'is_guest' => false, 'joined_at' => now()]
                );

                $wasRecentlyCreated = $participant->wasRecentlyCreated;
                $previousStatus = $wasRecentlyCreated ? null : $participant->status;
                $shouldBroadcastJoin = $wasRecentlyCreated;

                if ($userHasInProgress) {
                    $participant->update(['status' => 'taking_quiz']);
                } elseif ($participant->status !== 'joined') {
                    $participant->update([
                        'status' => 'joined',
                        'joined_at' => now(),
                        'left_at' => null,
                    ]);
                    $shouldBroadcastJoin = true;
                } else {
                    $participant->update([
                        'left_at' => null,
                    ]);
                }

                if ($previousStatus === 'taking_quiz' && $participant->fresh()?->status === 'joined') {
                    $shouldBroadcastJoin = true;
                }

                if ($shouldBroadcastJoin) {
                    $this->broadcastParticipantJoinedState($quiz, $participant->fresh(['user']));
                }
            }
        } else {
            // Guest user can stay associated with this quiz while it is active or while resuming an attempt
            $guestName = session('guest_name');
            if ($guestName && !$hasCompleted && !$quizEnded) {
                $participant = $this->findGuestParticipantBySession($quiz);
                $shouldBroadcastJoin = false;
                
                if (!$participant) {
                    $participant = QuizParticipant::create([
                        'quiz_id' => $quiz->id,
                        'session_id' => session()->getId(),
                        'guest_name' => $guestName,
                        'is_guest' => true,
                        'status' => 'joined',
                        'joined_at' => now()
                    ]);
                    $shouldBroadcastJoin = true;
                } else {
                    $previousStatus = $participant->status;
                    $participant->update([
                        'session_id' => session()->getId(),
                        'guest_name' => $guestName,
                        'status' => 'joined',
                        'joined_at' => $previousStatus === 'joined' ? $participant->joined_at : now(),
                        'left_at' => null,
                    ]);
                    $shouldBroadcastJoin = $previousStatus !== 'joined';
                }

                if ($shouldBroadcastJoin) {
                    $this->broadcastParticipantJoinedState($quiz, $participant->fresh(['user']));
                }
            }
        }

        // Get participants for display - ONLY those in lobby (joined status)
        $participants = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->orderBy('joined_at', 'asc')
            ->get()
            ->map(function ($p) {
                $displayName = $p->guest_name;
                if (!$p->is_guest && $p->user) {
                    $displayName = $p->user->name;
                } elseif (!$p->is_guest && !$p->user) {
                    $displayName = 'Unknown User';
                }
                
                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'name' => $displayName,
                    'is_guest' => $p->is_guest,
                    'joined_at' => $p->joined_at,
                    'updated_at' => $p->updated_at,
                    'status' => $p->status
                ];
            });

        $isPublicQuiz = $quiz->category_id === null;

        return view('user.quiz.lobby', compact(
            'quiz', 
            'participants', 
            'participant',
            'inProgressAttempt',
            'abandonedAttempt',
            'remainingAttempts',
            'completedAttempts',
            'quizStartedByAdmin',
            'quizEnded',
            'isPublicQuiz',
            'requiresLogin',
            'quizAlreadyStarted',
            'userHasInProgress',
            'hasCompleted',
            'completedAttempt'
        ));
    }

    public function join(Request $request, Quiz $quiz)
    {
        try {
            $guestName = $request->input('guest_name');
            $user = Auth::user();
            $existingParticipant = null;
            
            Log::info('Join request received', [
                'quiz_id' => $quiz->id,
                'has_user' => $user ? true : false,
                'guest_name' => $guestName
            ]);
            
            // Check if quiz is published
            if (!$quiz->is_published) {
                return response()->json([
                    'success' => false, 
                    'error' => 'This quiz is not available.'
                ], 403);
            }
            
            // CHECK IF QUIZ HAS ENDED - Time is over (MUST BE FIRST CHECK)
            if ($quiz->ends_at && $quiz->ends_at < now()) {
                Log::info('Blocked join attempt - quiz has ended', [
                    'quiz_id' => $quiz->id,
                    'ends_at' => $quiz->ends_at
                ]);
                return response()->json([
                    'success' => false, 
                    'error' => 'You missed the exam. Time is over.'
                ], 403);
            }
            
            // CHECK IF USER HAS REACHED MAX ATTEMPTS
            $hasCompleted = false;
            if ($user) {
                $completedAttempts = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->count();
                $hasCompleted = $quiz->max_attempts > 0 && $completedAttempts >= $quiz->max_attempts;
            } else {
                if ($guestName) {
                    $participant = $this->findGuestParticipantBySession($quiz);
                    if ($participant) {
                        $completedAttempts = QuizAttempt::where('participant_id', $participant->id)
                            ->where('quiz_id', $quiz->id)
                            ->where('status', 'completed')
                            ->count();
                        $hasCompleted = $quiz->max_attempts > 0 && $completedAttempts >= $quiz->max_attempts;
                    }
                }
            }
            
            if ($hasCompleted) {
                Log::info('Blocked join attempt - user already completed quiz', [
                    'quiz_id' => $quiz->id,
                    'user_id' => $user ? $user->id : null,
                    'guest_name' => $guestName
                ]);
                return response()->json([
                    'success' => false, 
                    'error' => 'You have reached the maximum number of attempts for this quiz.'
                ], 403);
            }
            
            $quizStartedByAdmin = $quiz->is_published
                && $quiz->scheduled_at
                && $quiz->scheduled_at <= now()
                && (!$quiz->ends_at || $quiz->ends_at > now());
            $quizAlreadyStarted = $quizStartedByAdmin;
            
            // Check if user already has an in-progress attempt (to allow resuming)
            $userHasInProgress = false;
            
            if ($user) {
                $existingParticipant = QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->first();

                $userHasInProgress = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'in_progress')
                    ->exists();
            } else {
                $existingParticipant = $this->findGuestParticipantBySession($quiz);

                if ($existingParticipant) {
                    $userHasInProgress = QuizAttempt::where('quiz_id', $quiz->id)
                        ->where('participant_id', $existingParticipant->id)
                        ->where('status', 'in_progress')
                        ->exists();
                }
            }

            $alreadyAllowedToEnter = $existingParticipant
                && in_array($existingParticipant->status, ['joined', 'taking_quiz', 'completed'], true);

            if ($quizAlreadyStarted && !$alreadyAllowedToEnter && !$userHasInProgress) {
                return response()->json([
                    'success' => false,
                    'error' => 'The quiz has already started. New participants cannot join now.'
                ], 403);
            }
            
            // For logged-in users, check remaining attempts
            if ($user) {
                $completedAttempts = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->count();
                
                $remainingAttempts = $quiz->max_attempts - $completedAttempts;
                
                if ($remainingAttempts <= 0 && $quiz->max_attempts > 0 && !$userHasInProgress) {
                    return response()->json([
                        'success' => false, 
                        'error' => 'You have used all your attempts for this quiz.'
                    ], 403);
                }
            }
            
            // Create or update participant
            $participant = null;
            
            $wasRecentlyCreated = false;
            $previousStatus = null;

            if ($user) {
                $previousStatus = $existingParticipant?->status;

                $participant = QuizParticipant::updateOrCreate(
                    ['quiz_id' => $quiz->id, 'user_id' => $user->id],
                    [
                        'status' => 'joined',
                        'joined_at' => now(),
                        'left_at' => null,
                        'is_guest' => false
                    ]
                );

                $wasRecentlyCreated = $existingParticipant === null;
            } else {
                // Guest user
                if (!$guestName || trim($guestName) == '') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Please enter your name to join the lobby.'
                    ], 422);
                }
                
                // Sanitize guest name
                $guestName = trim(strip_tags($guestName));
                if (strlen($guestName) < 2) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Name must be at least 2 characters long.'
                    ], 422);
                }
                
                // Check if guest already exists
                $participant = $existingParticipant;

                if ($this->isGuestNameAlreadyTaken($quiz, $guestName, $participant?->id)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This name is already being used in the lobby. Please choose a different name.'
                    ], 422);
                }

                // Store guest name in session
                session(['guest_name' => $guestName]);
                
                if ($participant) {
                    $previousStatus = $participant->status;
                    $participant->update([
                        'guest_name' => $guestName,
                        'status' => 'joined',
                        'joined_at' => now(),
                        'left_at' => null,
                        'session_id' => session()->getId(),
                    ]);
                } else {
                    $participant = QuizParticipant::create([
                        'quiz_id' => $quiz->id,
                        'session_id' => session()->getId(),
                        'guest_name' => $guestName,
                        'is_guest' => true,
                        'status' => 'joined',
                        'joined_at' => now()
                    ]);
                    $wasRecentlyCreated = true;
                }
            }
            
            if ($participant) {
                $participant = $participant->fresh(['user']);
            }

            if ($participant && ($wasRecentlyCreated || $previousStatus !== 'joined')) {
                $this->broadcastParticipantJoinedState($quiz, $participant);
            } else {
                $this->broadcastParticipantsPayload($quiz);
            }
            
            return response()->json([
                'success' => true,
                'is_guest' => !$user,
                'participant_id' => $participant->id,
                'message' => 'Successfully joined the lobby!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error joining quiz lobby', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to join lobby. Please try again.'
            ], 500);
        }
    }

    public function leave(Quiz $quiz)
    {
        try {
            $user = Auth::user();
            
            $participant = null;
            
            if ($user) {
                $participant = QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->first();
            } else {
                $guestName = session('guest_name');
                if ($guestName) {
                    $participant = $this->findGuestParticipantBySession($quiz);
                }
            }

            if ($participant && $participant->status === 'joined') {
                $participantName = $participant->guest_name ?? ($user ? $user->name : 'Guest');
                
                $participant->update([
                    'status' => 'left',
                    'left_at' => now()
                ]);
                
                Log::info('Participant left lobby', [
                    'participant_id' => $participant->id,
                    'name' => $participantName,
                    'quiz_id' => $quiz->id
                ]);
                
                try {
                    $participant = $participant->fresh(['user']);
                    broadcast(new ParticipantLeft($participant, $quiz));
                    $this->broadcastParticipantsPayload($quiz);
                } catch (\Exception $e) {
                    Log::warning('Broadcast failed: ' . $e->getMessage());
                }
                
                if (!$user) {
                    session()->forget('guest_name');
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully left the lobby'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Already left the lobby'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error leaving quiz lobby: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to leave lobby. Please try again.'
            ], 500);
        }
    }

    public function participants(Quiz $quiz)
    {
        try {
            $participants = QuizParticipant::with('user')
                ->where('quiz_id', $quiz->id)
                ->where('status', 'joined')
                ->orderBy('joined_at', 'asc')
                ->get()
                ->map(function ($participant) {
                    $displayName = $participant->guest_name;
                    if (!$participant->is_guest && $participant->user) {
                        $displayName = $participant->user->name;
                    } elseif (!$participant->is_guest && !$participant->user) {
                        $displayName = 'Unknown User';
                    }
                    
                    return [
                        'id' => $participant->id,
                        'name' => $displayName,
                        'is_guest' => $participant->is_guest,
                        'joined_at' => $participant->joined_at
                    ];
                });

            return response()->json($participants);
            
        } catch (\Exception $e) {
            Log::error('Error fetching participants', [
                'error' => $e->getMessage(),
                'quiz_id' => $quiz->id
            ]);
            
            return response()->json([], 500);
        }
    }
    
    public function heartbeat(Quiz $quiz)
    {
        try {
            $user = Auth::user();
            
            $participant = null;
            
            if ($user) {
                $participant = QuizParticipant::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->first();
            } else {
                $guestName = session('guest_name');
                if ($guestName) {
                    $participant = $this->findGuestParticipantBySession($quiz);
                }
            }
            
            if ($participant && $participant->status === 'joined') {
                $participant->update(['updated_at' => now()]);
                return response()->json(['success' => true]);
            }
            
            return response()->json(['success' => false, 'message' => 'Participant not found'], 404);
            
        } catch (\Exception $e) {
            Log::error('Heartbeat error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
    
    public function checkStatus(Quiz $quiz)
    {
        try {
            $quizStartedByAdmin = $quiz->is_published
                && $quiz->scheduled_at
                && $quiz->scheduled_at <= now()
                && (!$quiz->ends_at || $quiz->ends_at > now());
            $quizAlreadyStarted = $quizStartedByAdmin;
            $quizEnded = $quiz->ends_at && $quiz->ends_at < now();
            
            return response()->json([
                'is_published' => $quiz->is_published,
                'scheduled_at' => $quiz->scheduled_at,
                'ends_at' => $quiz->ends_at,
                'has_in_progress_attempts' => QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('status', 'in_progress')
                    ->exists(),
                'quiz_started_by_admin' => $quizStartedByAdmin,
                'quiz_already_started' => $quizAlreadyStarted,
                'quiz_ended' => $quizEnded
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

    private function isGuestNameAlreadyTaken(Quiz $quiz, string $guestName, ?int $ignoreParticipantId = null): bool
    {
        $normalizedGuestName = mb_strtolower(trim($guestName));

        return QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->when($ignoreParticipantId, function ($query) use ($ignoreParticipantId) {
                $query->where('id', '!=', $ignoreParticipantId);
            })
            ->get()
            ->contains(function (QuizParticipant $participant) use ($normalizedGuestName) {
                $displayName = $participant->is_guest
                    ? $participant->guest_name
                    : optional($participant->user)->name;

                if (!$displayName) {
                    return false;
                }

                return mb_strtolower(trim($displayName)) === $normalizedGuestName;
            });
    }

    private function broadcastParticipantJoinedState(Quiz $quiz, QuizParticipant $participant): void
    {
        try {
            broadcast(new ParticipantJoined($participant, $quiz));
            $this->broadcastParticipantsPayload($quiz);
        } catch (\Exception $e) {
            Log::warning('Joined-state broadcast failed: ' . $e->getMessage());
        }
    }

    private function broadcastParticipantsPayload(Quiz $quiz): void
    {
        broadcast(new QuizParticipantsUpdated(
            $quiz,
            $this->quizParticipantsPayloadService->build($quiz)
        ));
    }
}
