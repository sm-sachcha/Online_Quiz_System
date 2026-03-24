<?php

namespace App\Listeners;

use App\Models\UserActivity;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleUserLogin(Login $event)
    {
        UserActivity::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'action' => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => ['login_at' => now()]
        ]);
    }

    public function handleUserLogout(Logout $event)
    {
        if ($event->user) {
            UserActivity::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'action' => 'logout',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => ['logout_at' => now()]
            ]);
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