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
    
    // Users assigned to this category
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'category_users')
            ->withPivot('status', 'created_at', 'updated_at')
            ->withTimestamps();
    }
}