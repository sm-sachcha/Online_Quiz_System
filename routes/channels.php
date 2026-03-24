<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\QuizParticipant;

Broadcast::channel('quiz.{quizId}', function ($user, $quizId) {
    $participant = QuizParticipant::where('quiz_id', $quizId)
        ->where('user_id', $user->id)
        ->where('status', 'joined')
        ->first();
    
    if ($participant) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
    
    return false;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('admin', function ($user) {
    return $user->isAdmin();
});