<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use App\Events\QuestionBroadcasted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizService
{
    public function startQuiz(Quiz $quiz, $userId)
    {
        $attemptsCount = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();
        
        if ($attemptsCount >= $quiz->max_attempts) {
            throw new \Exception('Maximum attempts reached for this quiz.');
        }
        
        $attempt = QuizAttempt::create([
            'user_id' => $userId,
            'quiz_id' => $quiz->id,
            'started_at' => now(),
            'status' => 'in_progress',
            'total_questions' => $quiz->questions->count(),
            'ip_address' => request()->ip(),
            'score' => 0,
            'correct_answers' => 0,
            'incorrect_answers' => 0,
            'total_points' => 0
        ]);
        
        Log::info('Quiz started', [
            'attempt_id' => $attempt->id,
            'user_id' => $userId,
            'quiz_id' => $quiz->id
        ]);
        
        broadcast(new QuizStarted($quiz))->toOthers();
        
        return $attempt;
    }
    
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
            broadcast(new QuestionBroadcasted(
                $quiz, 
                $nextQuestion, 
                $questionNumber, 
                $quiz->questions->count()
            ));
        }
        
        return $nextQuestion;
    }
    
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
    
    public function endQuiz(Quiz $quiz)
    {
        broadcast(new QuizEnded($quiz))->toOthers();
        
        QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->update([
                'status' => 'abandoned',
                'ended_at' => now()
            ]);
    }
}