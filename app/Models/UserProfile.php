<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'phone', 
        'address', 
        'city', 
        'country',
        'profile_picture', 
        'bio', 
        'total_points', 
        'quizzes_attempted',
        'quizzes_won'
    ];

    protected $casts = [
        'total_points' => 'integer',
        'quizzes_attempted' => 'integer',
        'quizzes_won' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}