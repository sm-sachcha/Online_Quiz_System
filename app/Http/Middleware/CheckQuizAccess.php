<?php

namespace App\Http\Middleware;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckQuizAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get quiz from route
        $quiz = $request->route('quiz');
        
        if (!$quiz instanceof Quiz) {
            $quizId = $request->route('quiz');
            $quiz = Quiz::find($quizId);
        }

        if (!$quiz) {
            abort(404, 'Quiz not found.');
        }

        // Check if quiz is published
        if (!$quiz->is_published) {
            abort(404, 'Quiz not available.');
        }

        // Check if quiz is within scheduled time
        if ($quiz->scheduled_at && $quiz->scheduled_at > now()) {
            abort(403, 'This quiz has not started yet. It will start on ' . $quiz->scheduled_at->format('M d, Y h:i A'));
        }

        if ($quiz->ends_at && $quiz->ends_at < now()) {
            abort(403, 'This quiz has ended on ' . $quiz->ends_at->format('M d, Y h:i A'));
        }

        // Check if user has reached max attempts
        if (Auth::check()) {
            $user = Auth::user();
            $attemptsCount = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count();

            if ($attemptsCount >= $quiz->max_attempts) {
                abort(403, 'You have reached the maximum number of attempts (' . $quiz->max_attempts . ') for this quiz.');
            }
        }

        return $next($request);
    }
}