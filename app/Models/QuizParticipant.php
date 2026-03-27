<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'user_id', 'status', 'joined_at', 'left_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Helper methods
    public function isInLobby()
    {
        return $this->status === 'joined';
    }
    
    public function isTakingQuiz()
    {
        return $this->status === 'taking_quiz';
    }
    
    public function hasLeft()
    {
        return $this->status === 'left';
    }
    
    public function isRegistered()
    {
        return $this->status === 'registered';
    }
}