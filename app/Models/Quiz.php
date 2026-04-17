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
        'total_questions', 'passing_score', 'is_random_questions', 'is_random_options', 'is_published',
        'scheduled_at', 'ends_at', 'max_attempts', 'total_points', 'settings',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'is_random_questions' => 'boolean',
        'is_random_options' => 'boolean',
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
            $quiz->slug = static::generateUniqueSlug($quiz->title);
        });
        
        static::updating(function ($quiz) {
            if ($quiz->isDirty('title')) {
                $quiz->slug = static::generateUniqueSlug($quiz->title, $quiz->id);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'quiz';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
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

    /**
     * Get the category that the quiz belongs to
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the quiz
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the quiz
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the questions for the quiz
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Get the attempts for the quiz
     */
    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the leaderboard entries for the quiz
     */
    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Get the schedules for the quiz
     */
    public function schedules()
    {
        return $this->hasMany(QuizSchedule::class);
    }

    /**
     * Get the participants for the quiz
     */
    public function participants()
    {
        return $this->hasMany(QuizParticipant::class);
    }
    
    /**
     * Get active participants (joined or taking quiz)
     */
    public function activeParticipants()
    {
        return $this->participants()
            ->whereIn('status', ['joined', 'taking_quiz']);
    }
    
    /**
     * Get participants count
     */
    public function getParticipantsCountAttribute()
    {
        return $this->activeParticipants()->count();
    }
    
    /**
     * Check if quiz has started
     */
    public function getHasStartedAttribute()
    {
        if ($this->scheduled_at) {
            return $this->scheduled_at <= now();
        }
        return $this->is_published;
    }
    
    /**
     * Check if quiz has ended
     */
    public function getHasEndedAttribute()
    {
        if ($this->ends_at) {
            return $this->ends_at < now();
        }
        return false;
    }
    
    /**
     * Get quiz status text
     */
    public function getStatusTextAttribute()
    {
        if (!$this->is_published) {
            return 'Draft';
        }
        if ($this->has_ended) {
            return 'Ended';
        }
        if ($this->scheduled_at && $this->scheduled_at > now()) {
            return 'Scheduled';
        }
        return 'Active';
    }
    
    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        if (!$this->is_published) {
            return 'secondary';
        }
        if ($this->has_ended) {
            return 'danger';
        }
        if ($this->scheduled_at && $this->scheduled_at > now()) {
            return 'warning';
        }
        return 'success';
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
     * Scope for published quizzes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
    
    /**
     * Scope for draft quizzes
     */
    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }
    
    /**
     * Scope for scheduled quizzes
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }
    
    /**
     * Scope for ended quizzes
     */
    public function scopeEnded($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now());
    }
    
    /**
     * Scope for quizzes that are ready to start (scheduled time passed)
     */
    public function scopeReadyToStart($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
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
            ->where('status', 'completed')
            ->count();
            
        return $attemptsCount < $this->max_attempts;
    }
    
    /**
     * Get remaining attempts for a user
     */
    public function getRemainingAttempts($userId)
    {
        $completedAttempts = QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->id)
            ->where('status', 'completed')
            ->count();
            
        return max(0, $this->max_attempts - $completedAttempts);
    }
    
    /**
     * Get user's best attempt
     */
    public function getUserBestAttempt($userId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderBy('ended_at')
            ->first();
    }
    
    /**
     * Get user's best score
     */
    public function getUserBestScore($userId)
    {
        $bestAttempt = $this->getUserBestAttempt($userId);
        return $bestAttempt ? $bestAttempt->score : 0;
    }
    
    /**
     * Check if user has an in-progress attempt
     */
    public function hasInProgressAttempt($userId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->id)
            ->where('status', 'in_progress')
            ->exists();
    }
    
    /**
     * Get user's in-progress attempt
     */
    public function getInProgressAttempt($userId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->id)
            ->where('status', 'in_progress')
            ->first();
    }
    
    /**
     * Get user's abandoned attempt
     */
    public function getAbandonedAttempt($userId)
    {
        return QuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $this->id)
            ->where('status', 'abandoned')
            ->first();
    }
    
    /**
     * Calculate total points from all questions
     */
    public function calculateTotalPoints()
    {
        return $this->questions()->sum('points');
    }
    
    /**
     * Calculate total questions count
     */
    public function calculateTotalQuestions()
    {
        return $this->questions()->count();
    }
    
    /**
     * Get completion rate
     */
    public function getCompletionRateAttribute()
    {
        $totalAttempts = $this->attempts()->count();
        if ($totalAttempts === 0) {
            return 0;
        }
        
        $completedAttempts = $this->attempts()
            ->where('status', 'completed')
            ->count();
            
        return round(($completedAttempts / $totalAttempts) * 100, 1);
    }
    
    /**
     * Get average score
     */
    public function getAverageScoreAttribute()
    {
        return round($this->attempts()->avg('score') ?? 0, 1);
    }
    
    /**
     * Get total participants count
     */
    public function getTotalParticipantsAttribute()
    {
        return $this->attempts()
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count('user_id');
    }
    
    /**
     * Check if quiz is ready for admin start
     */
    public function getIsReadyForAdminStartAttribute()
    {
        return $this->is_published && 
               (!$this->scheduled_at || $this->scheduled_at <= now()) &&
               (!$this->ends_at || $this->ends_at >= now());
    }
    
    /**
     * Start quiz manually (admin override)
     */
    public function startManually()
    {
        $this->update([
            'is_published' => true,
            'scheduled_at' => now(),
            'ends_at' => now()->addMinutes($this->duration_minutes)
        ]);
        
        return $this;
    }
}
