<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'user_id', 'participant_id', 'score', 'rank', 'metadata'
    ];

    protected $casts = [
        'score' => 'integer',
        'rank' => 'integer',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function participant()
    {
        return $this->belongsTo(QuizParticipant::class, 'participant_id');
    }
}