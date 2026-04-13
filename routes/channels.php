<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\QuizParticipant;

Broadcast::channel('quiz.{quizId}', function ($user, $quizId) {
    // Allow anyone to listen to quiz channels for real-time updates
    return true;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('admin', function ($user) {
    return $user->isAdmin();
});