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
        'name', 'email', 'password', 'role', 'is_active'
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
    
    /**
     * Categories assigned to this user
     * Note: Table name is 'category_user' (singular, alphabetical order)
     */
    public function assignedCategories()
    {
        return $this->belongsToMany(Category::class, 'category_user', 'user_id', 'category_id')
                    ->withTimestamps();
    }
    
    /**
     * Get all quizzes available to this user through assigned categories
     */
    public function availableQuizzes()
    {
        $categoryIds = $this->assignedCategories()->pluck('categories.id');
        
        return Quiz::where('is_published', true)
            ->whereIn('category_id', $categoryIds)
            ->where(function($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
    
    /**
     * Check if user can take a specific quiz
     */
    public function canTakeQuiz(Quiz $quiz)
    {
        // Check if quiz belongs to an assigned category
        $assignedCategoryIds = $this->assignedCategories()->pluck('categories.id')->toArray();
        
        if (!in_array($quiz->category_id, $assignedCategoryIds)) {
            return false;
        }
        
        // Check max attempts
        $attemptsCount = QuizAttempt::where('user_id', $this->id)
            ->where('quiz_id', $quiz->id)
            ->count();
        
        return $attemptsCount < $quiz->max_attempts;
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