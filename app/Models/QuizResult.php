<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id', 
        'total_score', 
        'percentage', 
        'passed',
        'rank', 
        'question_wise_analysis', 
        'time_analysis'
    ];

    protected $casts = [
        'total_score' => 'integer',
        'percentage' => 'integer',
        'passed' => 'boolean',
        'rank' => 'integer',
        'question_wise_analysis' => 'array',
        'time_analysis' => 'array',
    ];

    public function quizAttempt()
    {
        return $this->belongsTo(QuizAttempt::class);
    }
}