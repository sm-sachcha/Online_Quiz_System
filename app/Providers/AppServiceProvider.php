<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        ResetPassword::createUrlUsing(function (object $user, string $token) {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
        });
        
        // Share common data with all views
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $view->with('authUser', auth()->user());
            }
        });
    }
}
