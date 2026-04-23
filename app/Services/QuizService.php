<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\QuizParticipant;
use App\Events\QuizEnded;
use App\Events\QuestionBroadcasted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizService
{
    /**
     * Start a new quiz attempt
     */
    public function startQuiz(Quiz $quiz, $userId = null, $participant = null)
    {
        Log::info('QuizService startQuiz called', [
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'has_participant' => !is_null($participant)
        ]);

        $startedAt = $quiz->resolveLiveStartedAt();

        $participantId = null;
        if ($participant) {
            $participantId = $participant->id;
        } elseif ($userId) {
            $existingParticipant = QuizParticipant::where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingParticipant) {
                $participantId = $existingParticipant->id;
            }
        }

        $questions = $quiz->questions()
            ->with('options')
            ->orderBy('order')
            ->get();

        $orderedQuestions = $quiz->is_random_questions
            ? $questions->shuffle()->values()
            : $questions->values();

        $questionSequence = $orderedQuestions->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $attempt = QuizAttempt::create([
            'user_id' => $userId,
            'participant_id' => $participantId,
            'quiz_id' => $quiz->id,
            'started_at' => $startedAt,
            'status' => 'in_progress',
            'total_questions' => count($questionSequence),
            'total_points' => $questions->sum('points'),
            'question_sequence' => $questionSequence,
            'option_sequences' => $this->buildOptionSequences($quiz, $orderedQuestions),
            'ip_address' => request()->ip()
        ]);

        Log::info('Quiz attempt created', [
            'attempt_id' => $attempt->id,
            'user_id' => $userId,
            'participant_id' => $participantId,
            'question_sequence' => $questionSequence
        ]);

        return $attempt;
    }
    
    /**
     * Get the next unanswered question for the attempt
     */
    public function getNextQuestion(Quiz $quiz, QuizAttempt $attempt)
    {
        $answeredQuestionIds = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->pluck('question_id')
            ->toArray();

        $questionSequence = collect(
            $attempt->question_sequence ?: $quiz->questions()->orderBy('order')->pluck('id')->all()
        );

        $nextQuestionId = $questionSequence
            ->first(fn ($questionId) => !in_array((int) $questionId, $answeredQuestionIds, true));

        $nextQuestion = $nextQuestionId
            ? Question::where('quiz_id', $quiz->id)->with('options')->find($nextQuestionId)
            : null;

        if ($nextQuestion) {
            $questionNumber = count($answeredQuestionIds) + 1;
            $totalQuestions = $quiz->questions->count();

            $this->applyAttemptOptionSequence($attempt, $nextQuestion);
            
            Log::info('Broadcasting next question', [
                'attempt_id' => $attempt->id,
                'question_id' => $nextQuestion->id,
                'question_number' => $questionNumber,
                'total_questions' => $totalQuestions
            ]);
            
            broadcast(new QuestionBroadcasted(
                $quiz, 
                $nextQuestion, 
                $questionNumber, 
                $totalQuestions
            ))->toOthers();
        }
        
        return $nextQuestion;
    }

    public function applyAttemptOptionSequence(QuizAttempt $attempt, ?Question $question): ?Question
    {
        if (!$question || !$question->relationLoaded('options')) {
            return $question;
        }

        $sequence = data_get($attempt->option_sequences, $question->id);
        if (!$sequence) {
            return $question;
        }

        $orderedOptions = collect($sequence)
            ->map(fn ($optionId) => $question->options->firstWhere('id', (int) $optionId))
            ->filter()
            ->values();

        if ($orderedOptions->isNotEmpty()) {
            $question->setRelation('options', $orderedOptions);
        }

        return $question;
    }
    
    /**
     * Submit a single answer (for single choice and true/false)
     */
    public function submitAnswer(QuizAttempt $attempt, $questionId, $optionId, $timeTaken)
    {
        try {
            return DB::transaction(function () use ($attempt, $questionId, $optionId, $timeTaken) {
                $lockedAttempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

                if ($lockedAttempt->status !== 'in_progress') {
                    throw new \RuntimeException('This attempt is already completed');
                }

                $existingAnswer = UserAnswer::where('quiz_attempt_id', $lockedAttempt->id)
                    ->where('question_id', $questionId)
                    ->first();

                if ($existingAnswer) {
                    throw new \RuntimeException('Question already answered');
                }

                $question = Question::with('options')
                    ->where('quiz_id', $lockedAttempt->quiz_id)
                    ->findOrFail($questionId);

                $isCorrect = false;
                $pointsEarned = 0;

                if ($optionId && $optionId !== '') {
                    $selectedOption = $question->options()->find($optionId);
                    if ($selectedOption) {
                        $isCorrect = $selectedOption->is_correct;
                        $pointsEarned = $isCorrect ? $question->points : 0;
                    }
                }

                Log::info('Submitting answer', [
                    'attempt_id' => $lockedAttempt->id,
                    'question_id' => $questionId,
                    'question_points' => $question->points,
                    'selected_option' => $optionId,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'time_taken' => $timeTaken
                ]);

                $answer = UserAnswer::create([
                    'quiz_attempt_id' => $lockedAttempt->id,
                    'question_id' => $questionId,
                    'option_id' => $optionId ?: null,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'time_taken_seconds' => $timeTaken
                ]);

                if ($isCorrect) {
                    $lockedAttempt->correct_answers++;
                } else {
                    $lockedAttempt->incorrect_answers++;
                }

                $lockedAttempt->score += $pointsEarned;
                $lockedAttempt->save();

                Log::info('Answer submitted successfully', [
                    'attempt_id' => $lockedAttempt->id,
                    'new_score' => $lockedAttempt->score,
                    'correct_answers' => $lockedAttempt->correct_answers,
                    'incorrect_answers' => $lockedAttempt->incorrect_answers
                ]);

                return $answer;
            });
        } catch (\Exception $e) {
            Log::error('Answer submission failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Submit multiple answers (for multiple choice questions)
     */
    public function submitMultipleAnswer(QuizAttempt $attempt, $questionId, $selectedOptions, $timeTaken)
    {
        $question = Question::with('options')->findOrFail($questionId);
        
        // Get correct options
        $correctOptions = $question->options->where('is_correct', true)->pluck('id')->toArray();
        
        // Sort both arrays for comparison
        $selectedSorted = $selectedOptions;
        $correctSorted = $correctOptions;
        sort($selectedSorted);
        sort($correctSorted);
        
        // Check if all correct options are selected and no extra options
        $isCorrect = ($selectedSorted == $correctSorted);
        $pointsEarned = $isCorrect ? $question->points : 0;
        
        Log::info('Submitting multiple choice answer', [
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'question_points' => $question->points,
            'selected_options' => $selectedOptions,
            'correct_options' => $correctOptions,
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned
        ]);
        
        DB::beginTransaction();
        try {
            // Create the answer record
            $answer = UserAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'answer_text' => json_encode($selectedOptions),
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'time_taken_seconds' => $timeTaken
            ]);
            
            // Update attempt totals
            if ($isCorrect) {
                $attempt->correct_answers++;
            } else {
                $attempt->incorrect_answers++;
            }
            
            $attempt->score += $pointsEarned;
            $attempt->total_points += $pointsEarned;
            $attempt->save();
            
            Log::info('Multiple choice answer submitted', [
                'attempt_id' => $attempt->id,
                'new_score' => $attempt->score
            ]);
            
            DB::commit();
            
            return $answer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Multiple choice submission failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * End the quiz and mark all in-progress attempts as abandoned
     */
    public function endQuiz(Quiz $quiz)
    {
        broadcast(new QuizEnded($quiz))->toOthers();
        
        QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->update([
                'status' => 'abandoned',
                'ended_at' => now()
            ]);
        
        Log::info('Quiz ended', [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title
        ]);
    }
    
    /**
     * Check if the quiz has been started by admin
     */
    public function isQuizStarted(Quiz $quiz)
    {
        return $quiz->is_published && $quiz->scheduled_at && $quiz->scheduled_at <= now();
    }
    
    /**
     * Check if the quiz has ended
     */
    public function isQuizEnded(Quiz $quiz)
    {
        return $quiz->ends_at && $quiz->ends_at < now();
    }
    
    /**
     * Get remaining time for quiz
     */
    public function getRemainingTime(Quiz $quiz)
    {
        if (!$quiz->ends_at) {
            return null;
        }
        
        $remaining = now()->diffInSeconds($quiz->ends_at, false);
        return $remaining > 0 ? $remaining : 0;
    }

    private function buildOptionSequences(Quiz $quiz, Collection $questions): array
    {
        $optionSequences = [];

        foreach ($questions as $question) {
            $options = $question->options->sortBy('order')->values();
            if ($quiz->is_random_options || $question->is_randomized_options) {
                $options = $options->shuffle()->values();
            }

            $optionSequences[$question->id] = $options
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return $optionSequences;
    }
}
