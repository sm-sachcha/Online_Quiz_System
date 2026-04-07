<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color', 'is_active', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
    
    /**
     * Get the users assigned to this category
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user', 'category_id', 'user_id')
                    ->withTimestamps();
    }
    
    /**
     * Alias for users() - for compatibility
     */
    public function assignedUsers()
    {
        return $this->users();
    }
}