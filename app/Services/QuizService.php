<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\QuizParticipant;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use App\Events\QuestionBroadcasted;
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
    
    // Get participant ID
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
    
    // Create attempt
    $attempt = QuizAttempt::create([
        'user_id' => $userId,
        'participant_id' => $participantId,
        'quiz_id' => $quiz->id,
        'started_at' => now(),
        'status' => 'in_progress',
        'total_questions' => $quiz->questions()->count(),
        'total_points' => $quiz->questions()->sum('points'),
        'ip_address' => request()->ip()
    ]);
    
    Log::info('Quiz attempt created', [
        'attempt_id' => $attempt->id,
        'user_id' => $userId,
        'participant_id' => $participantId
    ]);
    
    // Broadcast quiz started event
    broadcast(new QuizStarted($quiz))->toOthers();
    
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
        
        if ($quiz->is_random_questions) {
            $nextQuestion = Question::where('quiz_id', $quiz->id)
                ->whereNotIn('id', $answeredQuestionIds)
                ->inRandomOrder()
                ->first();
        } else {
            $nextQuestion = Question::where('quiz_id', $quiz->id)
                ->whereNotIn('id', $answeredQuestionIds)
                ->orderBy('order')
                ->first();
        }
        
        if ($nextQuestion) {
            $questionNumber = count($answeredQuestionIds) + 1;
            $totalQuestions = $quiz->questions->count();
            
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
    
    /**
     * Submit a single answer (for single choice and true/false)
     */
    public function submitAnswer(QuizAttempt $attempt, $questionId, $optionId, $timeTaken)
    {
        $question = Question::with('options')->findOrFail($questionId);
        
        // Calculate if answer is correct
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
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'question_points' => $question->points,
            'selected_option' => $optionId,
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'time_taken' => $timeTaken
        ]);
        
        DB::beginTransaction();
        try {
            // Create the answer record
            $answer = UserAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'option_id' => $optionId ?: null,
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
            
            Log::info('Answer submitted successfully', [
                'attempt_id' => $attempt->id,
                'new_score' => $attempt->score,
                'correct_answers' => $attempt->correct_answers,
                'incorrect_answers' => $attempt->incorrect_answers
            ]);
            
            DB::commit();
            
            return $answer;
        } catch (\Exception $e) {
            DB::rollBack();
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
}