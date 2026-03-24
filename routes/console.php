<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Quiz;
use App\Models\QuizSchedule;
use App\Models\QuizAttempt;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CleanupInactiveParticipants;

// Run cleanup every minute to remove users who haven't sent heartbeat
Schedule::command(CleanupInactiveParticipants::class)->everyMinute();


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Check and update quiz schedules
Artisan::command('quiz:check-schedules', function () {
    $this->info('Checking quiz schedules...');
    
    // Start scheduled quizzes
    $schedules = QuizSchedule::with('quiz')
        ->where('status', 'scheduled')
        ->where('scheduled_start', '<=', now())
        ->get();
    
    foreach ($schedules as $schedule) {
        // Get the actual Quiz model from the relation
        $quiz = $schedule->quiz;
        
        if ($quiz && $quiz instanceof Quiz) {
            $schedule->update(['status' => 'ongoing']);
            broadcast(new QuizStarted($quiz));
            $this->info("Started quiz: {$quiz->title}");
        } else {
            $this->error("Quiz not found for schedule ID: {$schedule->id}");
        }
    }
    
    // End expired quizzes
    $ongoingSchedules = QuizSchedule::with('quiz')
        ->where('status', 'ongoing')
        ->where('scheduled_end', '<=', now())
        ->get();
    
    foreach ($ongoingSchedules as $schedule) {
        // Get the actual Quiz model from the relation
        $quiz = $schedule->quiz;
        
        if ($quiz && $quiz instanceof Quiz) {
            $schedule->update(['status' => 'completed']);
            broadcast(new QuizEnded($quiz));
            $this->info("Ended quiz: {$quiz->title}");
        } else {
            $this->error("Quiz not found for schedule ID: {$schedule->id}");
        }
    }
    
    $this->info('Quiz schedule check completed.');
})->describe('Check and update quiz schedules')->everyMinute();

// Clean up abandoned quiz attempts
Artisan::command('quiz:cleanup', function () {
    $this->info('Cleaning up old quiz attempts...');
    
    $count = QuizAttempt::where('status', 'abandoned')
        ->where('created_at', '<', now()->subDays(7))
        ->delete();
    
    $this->info("Deleted {$count} abandoned attempts.");
})->describe('Clean up old quiz attempts')->daily();

// Cache all quiz leaderboards
Artisan::command('leaderboard:cache', function () {
    $this->info('Caching leaderboards...');
    
    // Get all published quizzes with their questions and attempts
    $quizzes = Quiz::with(['questions', 'attempts'])
        ->where('is_published', true)
        ->get();
    
    if ($quizzes->isEmpty()) {
        $this->info('No published quizzes found.');
        return;
    }
    
    $leaderboardService = app(LeaderboardService::class);
    $cachedCount = 0;
    
    foreach ($quizzes as $quiz) {
        try {
            // Verify it's a proper Quiz model instance
            if ($quiz instanceof Quiz) {
                $leaderboardService->updateLeaderboard($quiz);
                $this->info("Cached leaderboard for quiz: {$quiz->title} (ID: {$quiz->id})");
                $cachedCount++;
            } else {
                $this->error("Invalid quiz object for ID: {$quiz->id}");
            }
        } catch (\Exception $e) {
            $this->error("Failed to cache leaderboard for quiz ID {$quiz->id}: {$e->getMessage()}");
        }
    }
    
    $this->info("Leaderboard caching completed. Processed {$cachedCount} quizzes.");
})->describe('Cache all quiz leaderboards')->hourly();

// Generate system report
Artisan::command('system:report', function () {
    $this->info('Generating system report...');
    
    $totalUsers = \App\Models\User::count();
    $totalAdmins = \App\Models\User::whereIn('role', ['admin', 'master_admin'])->count();
    $totalQuizzes = Quiz::count();
    $publishedQuizzes = Quiz::where('is_published', true)->count();
    $totalAttempts = QuizAttempt::count();
    $completedAttempts = QuizAttempt::where('status', 'completed')->count();
    
    $this->info("\n==================================");
    $this->info("SYSTEM REPORT");
    $this->info("==================================");
    $this->info("Generated at: " . now());
    $this->info("----------------------------------");
    $this->info("Users:");
    $this->info("  - Total Users: {$totalUsers}");
    $this->info("  - Administrators: {$totalAdmins}");
    $this->info("----------------------------------");
    $this->info("Quizzes:");
    $this->info("  - Total Quizzes: {$totalQuizzes}");
    $this->info("  - Published: {$publishedQuizzes}");
    $this->info("  - Draft: " . ($totalQuizzes - $publishedQuizzes));
    $this->info("----------------------------------");
    $this->info("Attempts:");
    $this->info("  - Total Attempts: {$totalAttempts}");
    $this->info("  - Completed: {$completedAttempts}");
    $this->info("  - In Progress: " . QuizAttempt::where('status', 'in_progress')->count());
    $this->info("  - Abandoned: " . QuizAttempt::where('status', 'abandoned')->count());
    $this->info("==================================");
})->describe('Generate system report')->daily();

// Reset cheating logs for all attempts (admin command)
Artisan::command('quiz:reset-cheating-logs', function () {
    $this->info('Resetting cheating logs...');
    
    $updated = QuizAttempt::whereNotNull('cheating_logs')
        ->update(['cheating_logs' => null]);
    
    $this->info("Reset cheating logs for {$updated} attempts.");
})->describe('Reset all cheating logs')->daily();

// Generate daily statistics summary
Artisan::command('stats:daily', function () {
    $this->info('Generating daily statistics...');
    
    $date = now()->subDay()->format('Y-m-d');
    
    $newUsers = \App\Models\User::whereDate('created_at', $date)->count();
    $newQuizzes = Quiz::whereDate('created_at', $date)->count();
    $newAttempts = QuizAttempt::whereDate('created_at', $date)->count();
    
    $this->info("Statistics for {$date}:");
    $this->info("  - New Users: {$newUsers}");
    $this->info("  - New Quizzes: {$newQuizzes}");
    $this->info("  - New Attempts: {$newAttempts}");
    
    // Log to file
    \Illuminate\Support\Facades\Log::info("Daily Stats {$date}", [
        'new_users' => $newUsers,
        'new_quizzes' => $newQuizzes,
        'new_attempts' => $newAttempts,
    ]);
    
    $this->info('Daily statistics saved.');
})->describe('Generate daily statistics')->daily();

// Fix quiz totals (recalculate total_questions and total_points)
Artisan::command('quiz:fix-totals', function () {
    $this->info('Recalculating quiz totals...');
    
    $quizzes = Quiz::all();
    $fixed = 0;
    
    foreach ($quizzes as $quiz) {
        $totalQuestions = $quiz->questions()->count();
        $totalPoints = $quiz->questions()->sum('points');
        
        if ($quiz->total_questions != $totalQuestions || $quiz->total_points != $totalPoints) {
            $quiz->update([
                'total_questions' => $totalQuestions,
                'total_points' => $totalPoints,
            ]);
            $this->info("Fixed quiz: {$quiz->title} (Questions: {$totalQuestions}, Points: {$totalPoints})");
            $fixed++;
        }
    }
    
    $this->info("Fixed {$fixed} quizzes.");
})->describe('Recalculate quiz total questions and points');