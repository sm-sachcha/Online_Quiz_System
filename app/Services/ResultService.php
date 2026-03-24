<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizResult;
use App\Models\UserAnswer;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResultService
{
    /**
     * Calculate result for a quiz attempt
     */
    public function calculateResult(QuizAttempt $attempt)
    {
        $quiz = $attempt->quiz;
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->with('question')
            ->get();
        
        $totalScore = $answers->sum('points_earned');
        $percentage = ($quiz->total_points > 0) 
            ? round(($totalScore / $quiz->total_points) * 100) 
            : 0;
        
        $questionWiseAnalysis = [];
        foreach ($answers as $answer) {
            $questionWiseAnalysis[] = [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question->question_text,
                'selected_option_id' => $answer->option_id,
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned,
                'time_taken' => $answer->time_taken_seconds
            ];
        }
        
        $timeAnalysis = [
            'total_time_taken' => $attempt->ended_at ? $attempt->ended_at->diffInSeconds($attempt->started_at) : 0,
            'average_time_per_question' => $answers->avg('time_taken_seconds')
        ];
        
        // Update or create quiz result
        $result = QuizResult::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id],
            [
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'passed' => $percentage >= $quiz->passing_score,
                'question_wise_analysis' => $questionWiseAnalysis,
                'time_analysis' => $timeAnalysis
            ]
        );
        
        // Update user profile stats
        $this->updateUserProfile($attempt->user_id, $attempt, $result);
        
        return $result;
    }
    
    /**
     * Calculate final results for all attempts of a quiz
     */
    public function calculateFinalResults(Quiz $quiz)
    {
        // Use proper Eloquent query to get QuizAttempt models
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->with(['quiz', 'user'])
            ->get(); // This returns Collection of QuizAttempt models
        
        foreach ($attempts as $attempt) {
            // Ensure we're passing a QuizAttempt model, not a stdClass
            if ($attempt instanceof QuizAttempt) {
                $this->calculateResult($attempt);
            }
        }
        
        // Update leaderboard after all results are calculated
        $leaderboardService = app(LeaderboardService::class);
        $leaderboardService->updateLeaderboard($quiz);
    }
    
    /**
     * Get user's quiz history
     */
    public function getUserQuizHistory($userId)
    {
        return QuizAttempt::with(['quiz', 'result'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attempt) {
                return [
                    'attempt_id' => $attempt->id,
                    'quiz_title' => $attempt->quiz->title,
                    'started_at' => $attempt->started_at,
                    'ended_at' => $attempt->ended_at,
                    'score' => $attempt->score,
                    'percentage' => $attempt->result->percentage ?? 0,
                    'passed' => $attempt->result->passed ?? false,
                    'status' => $attempt->status
                ];
            });
    }
    
    /**
     * Get detailed result for a specific attempt
     */
    public function getDetailedResult($attemptId)
    {
        // Find the attempt by ID to ensure we have a proper model
        $attempt = QuizAttempt::with(['quiz', 'result', 'user'])
            ->findOrFail($attemptId);
        
        return $this->getDetailedResultForAttempt($attempt);
    }
    
    /**
     * Get detailed result for a specific attempt model
     */
    public function getDetailedResultForAttempt(QuizAttempt $attempt)
    {
        $result = $attempt->result;
        
        if (!$result) {
            $result = $this->calculateResult($attempt);
        }
        
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->with(['question', 'option'])
            ->get();
        
        $detailedAnswers = [];
        foreach ($answers as $answer) {
            $detailedAnswers[] = [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question->question_text,
                'user_answer' => $answer->option ? $answer->option->option_text : ($answer->answer_text ?? 'No answer'),
                'correct_answer' => $answer->question->options()->where('is_correct', true)->first()->option_text ?? 'N/A',
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned,
                'time_taken' => $answer->time_taken_seconds,
                'explanation' => $answer->question->explanation
            ];
        }
        
        return [
            'attempt' => $attempt,
            'result' => $result,
            'answers' => $detailedAnswers,
            'summary' => [
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'incorrect_answers' => $attempt->incorrect_answers,
                'score' => $attempt->score,
                'percentage' => $result->percentage,
                'passed' => $result->passed,
                'rank' => $result->rank,
                'started_at' => $attempt->started_at,
                'ended_at' => $attempt->ended_at,
                'time_taken' => $attempt->ended_at ? $attempt->ended_at->diffInMinutes($attempt->started_at) : 0
            ]
        ];
    }
    
    /**
     * Update user profile statistics
     */
    private function updateUserProfile($userId, QuizAttempt $attempt, QuizResult $result)
    {
        $profile = UserProfile::where('user_id', $userId)->first();
        
        if ($profile) {
            // Update total points
            $profile->increment('total_points', $attempt->score);
            
            // Update quizzes attempted count
            $profile->increment('quizzes_attempted');
            
            // Update quizzes won count if passed
            if ($result->passed) {
                $profile->increment('quizzes_won');
            }
            
            $profile->save();
        }
    }
    
    /**
     * Generate certificate data for passed quiz
     */
    public function generateCertificateData($attemptId)
    {
        $attempt = QuizAttempt::with(['user', 'quiz', 'result'])
            ->findOrFail($attemptId);
        
        $result = $attempt->result;
        
        if (!$result || !$result->passed) {
            return null;
        }
        
        return [
            'user_name' => $attempt->user->name,
            'quiz_title' => $attempt->quiz->title,
            'score' => $attempt->score,
            'percentage' => $result->percentage,
            'date' => $attempt->ended_at->format('F j, Y'),
            'certificate_id' => 'CERT-' . strtoupper(uniqid()),
            'rank' => $result->rank
        ];
    }
    
    /**
     * Calculate statistics for a quiz
     */
    public function getQuizStatistics(Quiz $quiz)
    {
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->with('result')
            ->get();
        
        $totalAttempts = $attempts->count();
        
        if ($totalAttempts === 0) {
            return [
                'total_attempts' => 0,
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'pass_rate' => 0,
                'average_time' => 0
            ];
        }
        
        $passedAttempts = $attempts->filter(function ($attempt) {
            return $attempt->result && $attempt->result->passed;
        })->count();
        
        return [
            'total_attempts' => $totalAttempts,
            'average_score' => round($attempts->avg('score'), 2),
            'highest_score' => $attempts->max('score'),
            'lowest_score' => $attempts->min('score'),
            'pass_rate' => round(($passedAttempts / $totalAttempts) * 100, 2),
            'average_time' => round($attempts->avg(function ($attempt) {
                return $attempt->ended_at ? $attempt->ended_at->diffInMinutes($attempt->started_at) : 0;
            }), 2)
        ];
    }
    
    /**
     * Get question-wise analysis for a quiz
     */
    public function getQuestionWiseAnalysis(Quiz $quiz)
    {
        $questions = $quiz->questions()->with('options')->get();
        $analysis = [];
        
        foreach ($questions as $question) {
            $answers = UserAnswer::where('question_id', $question->id)
                ->whereHas('quizAttempt', function ($query) use ($quiz) {
                    $query->where('quiz_id', $quiz->id)
                        ->where('status', 'completed');
                })
                ->get();
            
            $totalAnswers = $answers->count();
            $correctAnswers = $answers->where('is_correct', true)->count();
            
            // Calculate option distribution
            $optionDistribution = [];
            foreach ($question->options as $option) {
                $optionCount = $answers->where('option_id', $option->id)->count();
                $optionDistribution[$option->option_text] = [
                    'count' => $optionCount,
                    'percentage' => $totalAnswers > 0 ? round(($optionCount / $totalAnswers) * 100, 2) : 0,
                    'is_correct' => $option->is_correct
                ];
            }
            
            $analysis[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'total_answers' => $totalAnswers,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $totalAnswers - $correctAnswers,
                'correct_percentage' => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0,
                'average_time' => round($answers->avg('time_taken_seconds'), 2),
                'option_distribution' => $optionDistribution
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Calculate rank for all participants in a quiz
     */
    public function calculateRanks(Quiz $quiz)
    {
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->get();
        
        $rank = 1;
        foreach ($attempts as $attempt) {
            // Update rank in quiz result
            QuizResult::where('quiz_attempt_id', $attempt->id)
                ->update(['rank' => $rank]);
            
            $rank++;
        }
    }
    
    /**
     * Export results to CSV
     */
    public function exportToCsv(Quiz $quiz)
    {
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->with(['user', 'result'])
            ->where('status', 'completed')
            ->get();
        
        $csvData = [];
        $csvData[] = ['User Name', 'Email', 'Score', 'Percentage', 'Passed', 'Rank', 'Started At', 'Completed At'];
        
        foreach ($attempts as $attempt) {
            $csvData[] = [
                $attempt->user->name,
                $attempt->user->email,
                $attempt->score,
                $attempt->result->percentage ?? 0,
                $attempt->result->passed ? 'Yes' : 'No',
                $attempt->result->rank ?? 'N/A',
                $attempt->started_at,
                $attempt->ended_at
            ];
        }
        
        return $csvData;
    }
}