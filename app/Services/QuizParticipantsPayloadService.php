<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\Question;

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

        $attempts = QuizAttempt::with('answers')
            ->where('quiz_id', $quiz->id)
            ->whereIn('status', ['in_progress', 'completed'])
            ->orderByDesc('created_at')
            ->get();

        $questionTexts = Question::where('quiz_id', $quiz->id)
            ->pluck('question_text', 'id');

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

        $latestInProgressByKey = $attempts
            ->where('status', 'in_progress')
            ->groupBy(fn ($attempt) => $participantKey($attempt->user_id, $attempt->participant_id))
            ->map(fn ($group) => $group->first());

        $latestCompletedByKey = $attempts
            ->where('status', 'completed')
            ->groupBy(fn ($attempt) => $participantKey($attempt->user_id, $attempt->participant_id))
            ->map(fn ($group) => $group->first());

        $participants = QuizParticipant::with('user')
            ->where('quiz_id', $quiz->id)
            ->orderByRaw("
                CASE status
                    WHEN 'joined' THEN 0
                    WHEN 'taking_quiz' THEN 1
                    WHEN 'completed' THEN 2
                    WHEN 'left' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($participant) use (
                $participantKey,
                $inProgressKeys,
                $completedKeys,
                $latestInProgressByKey,
                $latestCompletedByKey,
                $quiz,
                $questionTexts,
            ) {
                $key = $participantKey($participant->user_id, $participant->id);
                $effectiveStatus = $participant->status;

                if ($key && $inProgressKeys->contains($key)) {
                    $effectiveStatus = 'taking_quiz';
                } elseif (
                    $key
                    && !in_array($participant->status, ['joined', 'taking_quiz'], true)
                    && $completedKeys->contains($key)
                ) {
                    $effectiveStatus = 'completed';
                }

                $latestInProgressAttempt = $key ? $latestInProgressByKey->get($key) : null;
                $latestCompletedAttempt = $key ? $latestCompletedByKey->get($key) : null;
                $latestAttempt = $latestInProgressAttempt ?: $latestCompletedAttempt;

                $currentQuestion = $this->resolveCurrentQuestionDetails(
                    $latestInProgressAttempt,
                    $quiz,
                    $questionTexts->all()
                );

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
                    'latest_attempt_id' => $latestCompletedAttempt?->id,
                    'active_attempt_id' => $latestInProgressAttempt?->id,
                    'correct_answers' => $latestAttempt?->correct_answers ?? 0,
                    'incorrect_answers' => $latestAttempt?->incorrect_answers ?? 0,
                    'answered_count' => $latestAttempt?->answers?->count() ?? 0,
                    'total_questions' => $latestAttempt?->total_questions ?? 0,
                    'current_question_number' => $currentQuestion['number'],
                    'current_question_text' => $currentQuestion['text'],
                ];
            })
            ->sort(function (array $left, array $right) {
                $statusRank = fn (string $status) => match ($status) {
                    'taking_quiz' => 0,
                    'joined' => 1,
                    'completed' => 2,
                    'left' => 3,
                    default => 4,
                };

                $leftStatusRank = $statusRank((string) ($left['effective_status'] ?? ''));
                $rightStatusRank = $statusRank((string) ($right['effective_status'] ?? ''));

                if ($leftStatusRank !== $rightStatusRank) {
                    return $leftStatusRank <=> $rightStatusRank;
                }

                if (($left['effective_status'] ?? null) === 'taking_quiz' && ($right['effective_status'] ?? null) === 'taking_quiz') {
                    $leftQuestion = (int) ($left['current_question_number'] ?? 0);
                    $rightQuestion = (int) ($right['current_question_number'] ?? 0);

                    if ($leftQuestion !== $rightQuestion) {
                        return $rightQuestion <=> $leftQuestion;
                    }
                }

                return strcmp((string) ($right['updated_at'] ?? ''), (string) ($left['updated_at'] ?? ''));
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

    private function resolveCurrentQuestionDetails(?QuizAttempt $attempt, Quiz $quiz, array $questionTexts): array
    {
        if (!$attempt || $attempt->status !== 'in_progress') {
            return ['number' => null, 'text' => null];
        }

        $sequence = collect(
            $attempt->question_sequence ?: $quiz->questions()->orderBy('order')->pluck('id')->all()
        )->map(fn ($id) => (int) $id)->values();

        $answeredQuestionIds = $attempt->answers
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $currentQuestionId = $sequence
            ->first(fn ($questionId) => !in_array((int) $questionId, $answeredQuestionIds, true));

        if (!$currentQuestionId) {
            return ['number' => null, 'text' => null];
        }

        $index = $sequence->search((int) $currentQuestionId);

        return [
            'number' => $index === false ? null : ((int) $index + 1),
            'text' => $questionTexts[$currentQuestionId] ?? null,
        ];
    }
}
