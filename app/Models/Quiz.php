<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'category_id', 'duration_minutes',
        'total_questions', 'passing_score', 'is_random_questions', 'is_published',
        'scheduled_at', 'ends_at', 'max_attempts', 'total_points', 'settings',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'is_random_questions' => 'boolean',
        'is_published' => 'boolean',
        'scheduled_at' => 'datetime',
        'ends_at' => 'datetime',
        'settings' => 'array',
        'total_questions' => 'integer',
        'total_points' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($quiz) {
            $quiz->slug = Str::slug($quiz->title);
        });
        
        static::updating(function ($quiz) {
            if ($quiz->isDirty('title')) {
                $quiz->slug = Str::slug($quiz->title);
            }
        });
    }

    /**
     * Update the total questions count and total points
     */
    public function updateTotals()
    {
        $this->total_questions = $this->questions()->count();
        $this->total_points = $this->questions()->sum('points');
        $this->saveQuietly();
        return $this;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }

    public function schedules()
    {
        return $this->hasMany(QuizSchedule::class);
    }

    public function participants()
    {
        return $this->hasMany(QuizParticipant::class);
    }
    
    /**
     * Scope for active quizzes
     */
    public function scopeActive($query)
    {
        return $query->where('is_published', true)
            ->where(function($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
    
    /**
     * Check if user can take this quiz
     */
    public function canBeTakenBy($userId)
    {
        if (!$this->is_published) {
            return false;
        }
        
        if ($this->scheduled_at && $this->scheduled_at > now()) {
            return false;
        }
        
        if ($this->ends_at && $this->ends_at < now()) {
            return false;
        }
        
        $attemptsCount = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->id)
            ->count();
            
        return $attemptsCount < $this->max_attempts;
    }
}