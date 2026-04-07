<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'question_text', 'question_type', 'points', 'time_seconds',
        'order', 'explanation', 'show_answer', 'metadata', 'is_active', 'created_by'
    ];

    protected $casts = [
        'points' => 'integer',
        'time_seconds' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'show_answer' => 'boolean',
        'metadata' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options()
    {
        return $this->hasMany(Option::class)->orderBy('order');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}