<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Quiz;
use App\Models\QuizSchedule;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use App\Events\ParticipantLeft;
use App\Services\LeaderboardService;
use App\Console\Commands\CleanupInactiveParticipants;

// ==================== INSPIRE COMMAND ====================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ==================== QUIZ SCHEDULE MANAGEMENT ====================
Artisan::command('quiz:check-schedules', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Checking quiz schedules...');
    
    // Start scheduled quizzes
    $schedules = QuizSchedule::with('quiz')
        ->where('status', 'scheduled')
        ->where('scheduled_start', '<=', now())
        ->get();
    
    $startedCount = 0;
    foreach ($schedules as $schedule) {
        $quiz = $schedule->quiz;
        
        if ($quiz && $quiz instanceof Quiz) {
            $schedule->update(['status' => 'ongoing']);
            broadcast(new QuizStarted($quiz));
            $this->info("  ✓ Started quiz: {$quiz->title}");
            $startedCount++;
        } else {
            $this->error("  ✗ Quiz not found for schedule ID: {$schedule->id}");
        }
    }
    
    if ($startedCount > 0) {
        $this->info("Started {$startedCount} quiz(es).");
    }
    
    // End expired quizzes
    $ongoingSchedules = QuizSchedule::with('quiz')
        ->where('status', 'ongoing')
        ->where('scheduled_end', '<=', now())
        ->get();
    
    $endedCount = 0;
    foreach ($ongoingSchedules as $schedule) {
        $quiz = $schedule->quiz;
        
        if ($quiz && $quiz instanceof Quiz) {
            $schedule->update(['status' => 'completed']);
            broadcast(new QuizEnded($quiz));
            $this->info("  ✓ Ended quiz: {$quiz->title}");
            $endedCount++;
        } else {
            $this->error("  ✗ Quiz not found for schedule ID: {$schedule->id}");
        }
    }
    
    if ($endedCount > 0) {
        $this->info("Ended {$endedCount} quiz(es).");
    }
    
    if ($startedCount === 0 && $endedCount === 0) {
        $this->info('No quiz schedule changes.');
    }
    
    $this->info('Quiz schedule check completed.');
})->describe('Check and update quiz schedules')->everyMinute();

// ==================== CLEANUP STALE PARTICIPANTS ====================
Artisan::command('participants:cleanup', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Cleaning up stale participants...');
    
    // Remove participants who haven't sent heartbeat for more than 2 minutes
    $staleParticipants = QuizParticipant::where('status', 'joined')
        ->where('updated_at', '<', now()->subMinutes(2))
        ->get();
    
    $cleanedCount = 0;
    foreach ($staleParticipants as $participant) {
        $participant->update([
            'status' => 'left',
            'left_at' => now()
        ]);
        
        $user = \App\Models\User::find($participant->user_id);
        $quiz = Quiz::find($participant->quiz_id);
        
        if ($user && $quiz) {
            broadcast(new ParticipantLeft($user, $quiz))->toOthers();
            $this->info("  ✓ Removed stale participant: {$user->name} from quiz: {$quiz->title}");
            $cleanedCount++;
        }
    }
    
    if ($cleanedCount > 0) {
        $this->info("Cleaned up {$cleanedCount} stale participant(s).");
    } else {
        $this->info('No stale participants found.');
    }
})->describe('Clean up stale participants')->everyMinute();

// ==================== CLEANUP ABANDONED ATTEMPTS ====================
Artisan::command('quiz:cleanup', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Cleaning up old quiz attempts...');
    
    $count = QuizAttempt::where('status', 'abandoned')
        ->where('created_at', '<', now()->subDays(7))
        ->delete();
    
    $this->info("Deleted {$count} abandoned attempt(s).");
})->describe('Clean up old quiz attempts')->daily();

// ==================== CACHE LEADERBOARDS ====================
Artisan::command('leaderboard:cache', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Caching leaderboards...');
    
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
    $failedCount = 0;
    
    foreach ($quizzes as $quiz) {
        try {
            if ($quiz instanceof Quiz) {
                $leaderboardService->updateLeaderboard($quiz);
                $this->info("  ✓ Cached leaderboard for: {$quiz->title} (ID: {$quiz->id})");
                $cachedCount++;
            } else {
                $this->error("  ✗ Invalid quiz object for ID: {$quiz->id}");
                $failedCount++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Failed to cache leaderboard for quiz ID {$quiz->id}: {$e->getMessage()}");
            $failedCount++;
        }
    }
    
    $this->info("Leaderboard caching completed. Processed: {$cachedCount} success, {$failedCount} failed.");
})->describe('Cache all quiz leaderboards')->hourly();

// ==================== SYSTEM REPORT ====================
Artisan::command('system:report', function () {
    $this->info('==================================');
    $this->info('SYSTEM REPORT');
    $this->info('==================================');
    $this->info('Generated at: ' . now());
    $this->info('----------------------------------');
    
    // Users
    $totalUsers = \App\Models\User::count();
    $totalAdmins = \App\Models\User::whereIn('role', ['admin', 'master_admin'])->count();
    $totalMasterAdmins = \App\Models\User::where('role', 'master_admin')->count();
    $regularUsers = $totalUsers - $totalAdmins;
    
    $this->info('USERS:');
    $this->info("  - Total Users: {$totalUsers}");
    $this->info("  - Master Admins: {$totalMasterAdmins}");
    $this->info("  - Regular Admins: " . ($totalAdmins - $totalMasterAdmins));
    $this->info("  - Regular Users: {$regularUsers}");
    $this->info('----------------------------------');
    
    // Quizzes
    $totalQuizzes = Quiz::count();
    $publishedQuizzes = Quiz::where('is_published', true)->count();
    $draftQuizzes = $totalQuizzes - $publishedQuizzes;
    $scheduledQuizzes = Quiz::whereNotNull('scheduled_at')
        ->where('scheduled_at', '>', now())
        ->count();
    $activeQuizzes = Quiz::where('is_published', true)
        ->where(function($q) {
            $q->whereNull('ends_at')
              ->orWhere('ends_at', '>=', now());
        })
        ->count();
    
    $this->info('QUIZZES:');
    $this->info("  - Total Quizzes: {$totalQuizzes}");
    $this->info("  - Published: {$publishedQuizzes}");
    $this->info("  - Draft: {$draftQuizzes}");
    $this->info("  - Scheduled: {$scheduledQuizzes}");
    $this->info("  - Active: {$activeQuizzes}");
    $this->info('----------------------------------');
    
    // Attempts
    $totalAttempts = QuizAttempt::count();
    $completedAttempts = QuizAttempt::where('status', 'completed')->count();
    $inProgressAttempts = QuizAttempt::where('status', 'in_progress')->count();
    $abandonedAttempts = QuizAttempt::where('status', 'abandoned')->count();
    
    $this->info('ATTEMPTS:');
    $this->info("  - Total Attempts: {$totalAttempts}");
    $this->info("  - Completed: {$completedAttempts}");
    $this->info("  - In Progress: {$inProgressAttempts}");
    $this->info("  - Abandoned: {$abandonedAttempts}");
    $this->info('----------------------------------');
    
    // Participants
    $activeParticipants = QuizParticipant::where('status', 'joined')
        ->where('updated_at', '>', now()->subMinutes(5))
        ->count();
    
    $this->info('ACTIVITY:');
    $this->info("  - Active Participants: {$activeParticipants}");
    $this->info('==================================');
    
})->describe('Generate system report')->daily();

// ==================== RESET CHEATING LOGS ====================
Artisan::command('quiz:reset-cheating-logs', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Resetting cheating logs...');
    
    $updated = QuizAttempt::whereNotNull('cheating_logs')
        ->update(['cheating_logs' => null]);
    
    $this->info("Reset cheating logs for {$updated} attempt(s).");
})->describe('Reset all cheating logs');

// ==================== DAILY STATISTICS ====================
Artisan::command('stats:daily', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Generating daily statistics...');
    
    $date = now()->subDay()->format('Y-m-d');
    
    $newUsers = \App\Models\User::whereDate('created_at', $date)->count();
    $newAdmins = \App\Models\User::whereIn('role', ['admin', 'master_admin'])
        ->whereDate('created_at', $date)
        ->count();
    $newQuizzes = Quiz::whereDate('created_at', $date)->count();
    $newAttempts = QuizAttempt::whereDate('created_at', $date)->count();
    $completedAttempts = QuizAttempt::whereDate('ended_at', $date)
        ->where('status', 'completed')
        ->count();
    
    $this->info("Statistics for {$date}:");
    $this->info("  - New Users: {$newUsers}");
    $this->info("  - New Admins: {$newAdmins}");
    $this->info("  - New Quizzes: {$newQuizzes}");
    $this->info("  - New Attempts: {$newAttempts}");
    $this->info("  - Completed Attempts: {$completedAttempts}");
    
    // Log to file
    \Illuminate\Support\Facades\Log::channel('daily')->info("Daily Stats {$date}", [
        'new_users' => $newUsers,
        'new_admins' => $newAdmins,
        'new_quizzes' => $newQuizzes,
        'new_attempts' => $newAttempts,
        'completed_attempts' => $completedAttempts,
    ]);
    
    $this->info('Daily statistics saved.');
})->describe('Generate daily statistics')->daily();

// ==================== FIX QUIZ TOTALS ====================
Artisan::command('quiz:fix-totals', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Recalculating quiz totals...');
    
    $quizzes = Quiz::all();
    $fixed = 0;
    $errors = 0;
    
    foreach ($quizzes as $quiz) {
        try {
            $totalQuestions = $quiz->questions()->count();
            $totalPoints = $quiz->questions()->sum('points');
            
            if ($quiz->total_questions != $totalQuestions || $quiz->total_points != $totalPoints) {
                $quiz->update([
                    'total_questions' => $totalQuestions,
                    'total_points' => $totalPoints,
                ]);
                $this->info("  ✓ Fixed quiz: {$quiz->title} (Questions: {$totalQuestions}, Points: {$totalPoints})");
                $fixed++;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error fixing quiz {$quiz->title}: {$e->getMessage()}");
            $errors++;
        }
    }
    
    $this->info("Quiz totals fixed: {$fixed} updated, {$errors} errors.");
})->describe('Recalculate quiz total questions and points');

// ==================== USER ACTIVITY REPORT ====================
Artisan::command('user:activity-report', function () {
    $this->info('[' . now()->format('Y-m-d H:i:s') . '] Generating user activity report...');
    
    $lastWeek = now()->subDays(7);
    
    $activeUsers = \App\Models\UserActivity::where('created_at', '>=', $lastWeek)
        ->distinct('user_id')
        ->count('user_id');
    
    $totalActivities = \App\Models\UserActivity::where('created_at', '>=', $lastWeek)->count();
    $loginCount = \App\Models\UserActivity::where('action', 'login')
        ->where('created_at', '>=', $lastWeek)
        ->count();
    $logoutCount = \App\Models\UserActivity::where('action', 'logout')
        ->where('created_at', '>=', $lastWeek)
        ->count();
    
    $this->info('User Activity Report (Last 7 Days):');
    $this->info("  - Active Users: {$activeUsers}");
    $this->info("  - Total Activities: {$totalActivities}");
    $this->info("  - Logins: {$loginCount}");
    $this->info("  - Logouts: {$logoutCount}");
    
})->describe('Generate user activity report')->weekly();

// ==================== MIGRATION STATUS ====================
Artisan::command('migration:status', function () {
    $this->info('Checking migration status...');
    
    $migrations = \Illuminate\Support\Facades\DB::table('migrations')
        ->orderBy('batch', 'desc')
        ->get();
    
    $this->info("Total migrations run: " . $migrations->count());
    $this->info("Last batch: " . ($migrations->first()->batch ?? 0));
    
})->describe('Show migration status');

// ==================== SCHEDULE COMMANDS ====================
// Register all scheduled commands
Schedule::command('participants:cleanup')->everyMinute();
Schedule::command('quiz:check-schedules')->everyMinute();
Schedule::command('leaderboard:cache')->hourly();
Schedule::command('stats:daily')->daily();
Schedule::command('quiz:cleanup')->daily();
Schedule::command('system:report')->daily();
Schedule::command('user:activity-report')->weekly();

