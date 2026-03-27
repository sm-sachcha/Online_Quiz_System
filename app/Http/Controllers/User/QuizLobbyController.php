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

class QuizLobbyController extends Controller
{
    public function index(Quiz $quiz)
    {
        if (!$quiz->is_published) {
            abort(404);
        }

        $user = Auth::user();
        
        // Check if user has an in-progress attempt
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
        
        // If user has an in-progress attempt, update status to joined
        if ($inProgressAttempt) {
            $participant->update([
                'status' => 'joined',
                'joined_at' => now(),
                'updated_at' => now()
            ]);
        }

        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->orderBy('joined_at', 'desc')
            ->get();

        return view('user.quiz.lobby', compact(
            'quiz', 
            'participants', 
            'participant',
            'inProgressAttempt',
            'remainingAttempts',
            'completedAttempts'
        ));
    }

    public function join(Quiz $quiz)
    {
        $user = Auth::user();
        
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
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => 'joined',
                'joined_at' => now(),
                'updated_at' => now()
            ]);

            broadcast(new ParticipantJoined($user, $quiz))->toOthers();
        }

        return response()->json(['success' => true]);
    }

    public function leave(Quiz $quiz)
    {
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
        
        // Check if user has an in-progress attempt
        $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->first();
        
        // If user has in-progress attempt, mark as abandoned
        if ($inProgressAttempt) {
            $inProgressAttempt->update([
                'status' => 'abandoned',
                'ended_at' => now()
            ]);
            
            broadcast(new UserDisconnected($user, $quiz))->toOthers();
        }

        return response()->json(['success' => true]);
    }

    public function participants(Quiz $quiz)
    {
        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->get()
            ->map(function ($participant) {
                // Check if user has an in-progress attempt
                $hasActiveAttempt = QuizAttempt::where('user_id', $participant->user_id)
                    ->where('quiz_id', $participant->quiz_id)
                    ->where('status', 'in_progress')
                    ->exists();
                
                return [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'joined_at' => $participant->joined_at,
                    'is_active' => $hasActiveAttempt,
                    'status' => $hasActiveAttempt ? 'taking_quiz' : 'in_lobby'
                ];
            });

        return response()->json($participants);
    }
    
    public function checkStatus(Quiz $quiz)
    {
        $user = Auth::user();
        
        $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->first();
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();
        
        return response()->json([
            'has_active_attempt' => $inProgressAttempt ? true : false,
            'attempt_id' => $inProgressAttempt ? $inProgressAttempt->id : null,
            'participant_status' => $participant ? $participant->status : 'none',
            'joined' => $participant && $participant->status == 'joined'
        ]);
    }
}