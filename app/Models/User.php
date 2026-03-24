<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'role', 
        'is_active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function createdQuizzes()
    {
        return $this->hasMany(Quiz::class, 'created_by');
    }

    public function createdCategories()
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    public function createdQuestions()
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function leaderboardEntries()
    {
        return $this->hasMany(Leaderboard::class);
    }

    public function quizParticipants()
    {
        return $this->hasMany(QuizParticipant::class);
    }

    public function isMasterAdmin()
    {
        return $this->role === 'master_admin';
    }

    public function isAdmin()
    {
        return $this->role === 'admin' || $this->role === 'master_admin';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }
}