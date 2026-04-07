<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Collection;

class RandomQuestionService
{
    public function generateRandomQuestions(Quiz $quiz, $count = null)
    {
        $questionCount = $count ?? $quiz->total_questions;
        
        return Question::where('quiz_id', $quiz->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($questionCount)
            ->get();
    }
    
    public function shuffleQuestions(Collection $questions)
    {
        return $questions->shuffle();
    }
    
    public function ensureUniqueQuestions(Quiz $quiz, array $excludedQuestionIds, $count)
    {
        return Question::where('quiz_id', $quiz->id)
            ->whereNotIn('id', $excludedQuestionIds)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }
}