<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'user_id', 'session_id', 'guest_name', 'device_id', 'is_guest', 
        'status', 'joined_at', 'left_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_guest' => 'boolean',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class, 'participant_id');
    }
    
    public function leaderboardEntries()
    {
        return $this->hasMany(Leaderboard::class, 'participant_id');
    }
}
