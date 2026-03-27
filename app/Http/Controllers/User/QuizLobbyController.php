<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use App\Models\QuizAttempt;
use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
use App\Events\UserDisconnected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuizLobbyController extends Controller
{
    public function index(Quiz $quiz)
    {
        if (!$quiz->is_published) {
            abort(404);
        }

        $user = Auth::user();
        
        // Check if user has an abandoned attempt
        $abandonedAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'abandoned')
            ->first();
        
        // Check if user has an in-progress attempt (taking quiz)
        $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->first();
        
        // Check if user has completed attempts
        $completedAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();
        
        $remainingAttempts = max(0, $quiz->max_attempts - $completedAttempts);
        
        // Get or create participant
        $participant = QuizParticipant::firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'user_id' => $user->id
            ],
            [
                'status' => 'registered'
            ]
        );
        
        // Update participant status based on current state
        if ($inProgressAttempt) {
            // User is actively taking quiz
            $participant->update([
                'status' => 'taking_quiz',
                'joined_at' => $participant->joined_at ?? now(),
                'updated_at' => now()
            ]);
        } elseif ($participant->status === 'joined' || $participant->status === 'registered') {
            // User is in lobby waiting
            $participant->update([
                'status' => 'joined',
                'joined_at' => $participant->joined_at ?? now(),
                'updated_at' => now()
            ]);
        }

        // Get all participants for display
        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->whereIn('status', ['joined', 'taking_quiz'])
            ->orderBy('joined_at', 'desc')
            ->get();

        return view('user.quiz.lobby', compact(
            'quiz', 
            'participants', 
            'participant',
            'inProgressAttempt',
            'abandonedAttempt',
            'remainingAttempts',
            'completedAttempts'
        ));
    }

    public function join(Quiz $quiz)
    {
        try {
            $user = Auth::user();
            
            // Check if quiz is published
            if (!$quiz->is_published) {
                return response()->json([
                    'success' => false, 
                    'error' => 'This quiz is not available.'
                ], 403);
            }
            
            // Check if quiz is scheduled for future
            if ($quiz->scheduled_at && $quiz->scheduled_at > now()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This quiz has not started yet. It will start on ' . $quiz->scheduled_at->format('M d, Y h:i A')
                ], 403);
            }
            
            // Check if quiz has ended
            if ($quiz->ends_at && $quiz->ends_at < now()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This quiz has already ended.'
                ], 403);
            }
            
            // Check if user has any remaining attempts
            $completedAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();
            
            $remainingAttempts = $quiz->max_attempts - $completedAttempts;
            
            if ($remainingAttempts <= 0 && $quiz->max_attempts > 0) {
                return response()->json([
                    'success' => false, 
                    'error' => 'You have used all your attempts for this quiz.'
                ], 403);
            }
            
            // Check if user already has a completed attempt and no attempts left
            if ($completedAttempts >= $quiz->max_attempts && $quiz->max_attempts > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'You have reached the maximum number of attempts for this quiz.'
                ], 403);
            }
            
            // Get or create participant and set status to 'joined'
            $participant = QuizParticipant::updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'user_id' => $user->id
                ],
                [
                    'status' => 'joined',
                    'joined_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // Broadcast to other participants
            broadcast(new ParticipantJoined($user, $quiz))->toOthers();
            
            Log::info('User joined lobby', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'status' => $participant->status
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error joining quiz lobby', [
                'error' => $e->getMessage(),
                'quiz_id' => $quiz->id,
                'user_id' => Auth::id()
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
            
            $participant = QuizParticipant::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->first();

            if ($participant) {
                $participant->update([
                    'status' => 'left',
                    'left_at' => now()
                ]);

                broadcast(new ParticipantLeft($user, $quiz))->toOthers();
            }
            
            Log::info('User left lobby', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id
            ]);

            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Error leaving quiz lobby', [
                'error' => $e->getMessage(),
                'quiz_id' => $quiz->id,
                'user_id' => Auth::id()
            ]);
            
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
                ->whereIn('status', ['joined', 'taking_quiz'])
                ->get()
                ->map(function ($participant) {
                    // Check if user has an in-progress attempt (taking quiz)
                    $hasActiveAttempt = QuizAttempt::where('user_id', $participant->user_id)
                        ->where('quiz_id', $participant->quiz_id)
                        ->where('status', 'in_progress')
                        ->exists();
                    
                    // Determine status based on database and actual state
                    $status = $participant->status;
                    if ($hasActiveAttempt && $status !== 'taking_quiz') {
                        // Update status if inconsistency found
                        $participant->update(['status' => 'taking_quiz']);
                        $status = 'taking_quiz';
                    }
                    
                    return [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                        'joined_at' => $participant->joined_at,
                        'is_active' => $hasActiveAttempt,
                        'status' => $status,
                        'display_status' => $hasActiveAttempt ? 'Taking Quiz' : 'In Lobby'
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
            
            // Check if user has an in-progress attempt
            $hasActiveAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'in_progress')
                ->exists();
            
            $participant = QuizParticipant::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->first();
            
            if ($participant) {
                $newStatus = $hasActiveAttempt ? 'taking_quiz' : 'joined';
                $participant->update([
                    'status' => $newStatus,
                    'updated_at' => now()
                ]);
            }
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
    
    public function markLeft(Quiz $quiz)
    {
        try {
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
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}