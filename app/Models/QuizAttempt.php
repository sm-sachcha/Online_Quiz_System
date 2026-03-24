<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'quiz_id', 
        'score', 
        'total_points', 
        'correct_answers',
        'incorrect_answers', 
        'total_questions', 
        'started_at', 
        'ended_at',
        'status', 
        'cheating_logs', 
        'ip_address'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cheating_logs' => 'array',
        'score' => 'integer',
        'total_points' => 'integer',
        'correct_answers' => 'integer',
        'incorrect_answers' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function result()
    {
        return $this->hasOne(QuizResult::class);
    }
}