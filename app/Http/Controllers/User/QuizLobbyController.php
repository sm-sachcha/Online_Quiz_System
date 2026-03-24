<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use App\Models\QuizAttempt;
use App\Models\Leaderboard;
use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
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
        
        // Check if quiz has max attempts
        $completedAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();
        
        $remainingAttempts = $quiz->max_attempts - $completedAttempts;
        $hasAttemptsLeft = $remainingAttempts > 0;
        
        // Check for in-progress attempt
        $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->first();
        
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

        // Get user's best rank for this quiz
        $userRank = null;
        $bestScore = null;
        $totalParticipants = null;
        
        $leaderboardEntry = Leaderboard::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->orderBy('rank')
            ->first();
        
        if ($leaderboardEntry) {
            $userRank = $leaderboardEntry->rank;
            $bestScore = $leaderboardEntry->score;
            $totalParticipants = Leaderboard::where('quiz_id', $quiz->id)->count();
        } else {
            // Check if user has any completed attempts
            $bestAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->orderByDesc('score')
                ->first();
            
            if ($bestAttempt) {
                $bestScore = $bestAttempt->score;
                // Calculate rank
                $higherScores = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->where('score', '>', $bestAttempt->score)
                    ->count();
                $userRank = $higherScores + 1;
                $totalParticipants = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->count();
            }
        }

        // Only get participants with status 'joined' (active in lobby)
        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->get();

        return view('user.quiz.lobby', compact(
            'quiz', 
            'participants', 
            'participant',
            'hasAttemptsLeft',
            'remainingAttempts',
            'completedAttempts',
            'inProgressAttempt',
            'userRank',
            'bestScore',
            'totalParticipants'
        ));
    }

    public function join(Quiz $quiz)
    {
        $user = Auth::user();
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            $participant = QuizParticipant::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'status' => 'joined',
                'joined_at' => now()
            ]);
        } else {
            $participant->update([
                'status' => 'joined',
                'joined_at' => now()
            ]);
        }

        broadcast(new ParticipantJoined($user, $quiz))->toOthers();
        
        return response()->json(['success' => true, 'status' => 'joined']);
    }

    public function leave(Quiz $quiz)
    {
        $user = Auth::user();
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant && $participant->status === 'joined') {
            $participant->update([
                'status' => 'left',
                'left_at' => now()
            ]);

            broadcast(new ParticipantLeft($user, $quiz))->toOthers();
            
            return response()->json(['success' => true, 'status' => 'left']);
        }

        return response()->json(['success' => false, 'error' => 'Participant not found'], 404);
    }

    public function participants(Quiz $quiz)
    {
        // Only return participants with status 'joined'
        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'joined_at' => $participant->joined_at
                ];
            });

        return response()->json($participants);
    }

    public function heartbeat(Quiz $quiz)
    {
        $user = Auth::user();
        
        $participant = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($participant && $participant->status === 'joined') {
            $participant->update(['updated_at' => now()]);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 404);
    }
}