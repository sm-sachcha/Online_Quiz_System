<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 
        'scheduled_start', 
        'scheduled_end', 
        'status', 
        'reminders_sent'
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'reminders_sent' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}