<?php

namespace App\Listeners;

use App\Models\UserActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LogUserActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleUserLogin(Login $event)
    {
        try {
            $userId = $event->user ? $event->user->getAuthIdentifier() : null;
            
            if ($userId) {
                UserActivity::create([
                    'user_id' => $userId,
                    'action' => 'login',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'details' => ['login_at' => now()]
                ]);
                Log::info('Login activity logged for user: ' . $userId);
            }
        } catch (\Exception $e) {
            Log::error('LogUserActivity (login) failed: ' . $e->getMessage());
        }
    }

    public function handleUserLogout(Logout $event)
    {
        try {
            $userId = $event->user ? $event->user->getAuthIdentifier() : null;
            
            if ($userId) {
                UserActivity::create([
                    'user_id' => $userId,
                    'action' => 'logout',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'details' => ['logout_at' => now()]
                ]);
                Log::info('Logout activity logged for user: ' . $userId);
            }
        } catch (\Exception $e) {
            Log::error('LogUserActivity (logout) failed: ' . $e->getMessage());
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            Login::class,
            [LogUserActivity::class, 'handleUserLogin']
        );

        $events->listen(
            Logout::class,
            [LogUserActivity::class, 'handleUserLogout']
        );
    }
}