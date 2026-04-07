<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;

class QuizParticipantsPayloadService
{
    public function build(Quiz $quiz): array
    {
        $participantKey = function ($userId, $participantId) {
            if ($userId) {
                return 'user:' . $userId;
            }

            if ($participantId) {
                return 'participant:' . $participantId;
            }

            return null;
        };

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->whereIn('status', ['in_progress', 'completed'])
            ->orderByDesc('created_at')
            ->get();

        $inProgressKeys = $attempts
            ->where('status', 'in_progress')
            ->map(fn ($attempt) => $participantKey($attempt->user_id, $attempt->participant_id))
            ->filter()
            ->unique()
            ->values();

        $completedKeys = $attempts
            ->where('status', 'completed')
            ->map(fn ($attempt) => $participantKey($attempt->user_id, $attempt->participant_id))
            ->filter()
            ->unique()
            ->values();

        $latestCompletedByKey = $attempts
            ->where('status', 'completed')
            ->mapWithKeys(function ($attempt) use ($participantKey) {
                return [$participantKey($attempt->user_id, $attempt->participant_id) => $attempt];
            });

        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->orderByRaw("FIELD(status, 'joined', 'taking_quiz', 'completed', 'left')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($participant) use ($participantKey, $inProgressKeys, $completedKeys, $latestCompletedByKey) {
                $key = $participantKey($participant->user_id, $participant->id);
                $effectiveStatus = $participant->status;

                if ($key && $inProgressKeys->contains($key)) {
                    $effectiveStatus = 'taking_quiz';
                } elseif ($key && $completedKeys->contains($key)) {
                    $effectiveStatus = 'completed';
                }

                $latestAttempt = $key ? $latestCompletedByKey->get($key) : null;

                return [
                    'id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'participant_key' => $key,
                    'name' => $participant->user ? $participant->user->name : ($participant->guest_name ?? 'Guest'),
                    'email' => $participant->user ? $participant->user->email : null,
                    'is_guest' => (bool) $participant->is_guest,
                    'status' => $participant->status,
                    'effective_status' => $effectiveStatus,
                    'joined_at' => optional($participant->joined_at)->toIso8601String(),
                    'updated_at' => optional($participant->updated_at)->toIso8601String(),
                    'latest_attempt_id' => $latestAttempt?->id,
                ];
            })
            ->values();

        $lobbyParticipants = $participants
            ->whereIn('effective_status', ['joined', 'taking_quiz'])
            ->values();

        $lobbyUsers = $participants->where('effective_status', 'joined')->count();
        $takingQuizCount = $participants->where('effective_status', 'taking_quiz')->count();
        $completedParticipants = $participants->where('effective_status', 'completed')->count();
        $leftParticipants = $participants->where('effective_status', 'left')->count();

        return [
            'participants' => $participants,
            'lobby_participants' => $lobbyParticipants,
            'activeParticipants' => $lobbyUsers + $takingQuizCount,
            'takingQuizCount' => $takingQuizCount,
            'lobbyUsers' => $lobbyUsers,
            'completedParticipants' => $completedParticipants,
            'leftParticipants' => $leftParticipants,
            'isQuizStarted' => $quiz->is_published && $quiz->scheduled_at && $quiz->scheduled_at <= now(),
            'hasQuestions' => $quiz->questions()->count() > 0,
        ];
    }
}
