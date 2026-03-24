<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for roles
        Gate::define('access-admin', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('access-master-admin', function ($user) {
            return $user->isMasterAdmin();
        });

        Gate::define('manage-users', function ($user) {
            return $user->isMasterAdmin();
        });

        Gate::define('manage-admins', function ($user) {
            return $user->isMasterAdmin();
        });

        Gate::define('manage-quizzes', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-categories', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('view-reports', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('take-quiz', function ($user, $quiz) {
            if (!$quiz->is_published) {
                return false;
            }
            
            if ($quiz->scheduled_at && $quiz->scheduled_at > now()) {
                return false;
            }
            
            if ($quiz->ends_at && $quiz->ends_at < now()) {
                return false;
            }
            
            $attemptsCount = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->count();
            
            return $attemptsCount < $quiz->max_attempts;
        });
    }
}