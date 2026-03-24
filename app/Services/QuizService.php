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

class QuizService
{
    public function startQuiz(Quiz $quiz, $userId)
    {
        $attemptsCount = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quiz->id)
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
            'ip_address' => request()->ip()
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
        $selectedOption = $question->options()->find($optionId);
        
        if (!$selectedOption) {
            throw new \Exception('Invalid option selected.');
        }
        
        $isCorrect = $selectedOption->is_correct;
        $pointsEarned = $isCorrect ? $question->points : 0;
        
        DB::beginTransaction();
        try {
            $answer = UserAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'option_id' => $optionId,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'time_taken_seconds' => $timeTaken
            ]);
            
            $attempt->score += $pointsEarned;
            $attempt->total_points += $pointsEarned;
            
            if ($isCorrect) {
                $attempt->correct_answers++;
            } else {
                $attempt->incorrect_answers++;
            }
            
            $attempt->save();
            
            DB::commit();
            
            return $answer;
        } catch (\Exception $e) {
            DB::rollBack();
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