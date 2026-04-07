<?php

namespace App\Providers;

use App\Events\AnswerSubmitted;
use App\Events\LeaderboardUpdated;
use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
use App\Events\QuestionBroadcasted;
use App\Events\QuizEnded;
use App\Events\QuizStarted;
use App\Events\UserDisconnected;
use App\Listeners\BroadcastNextQuestion;
use App\Listeners\CacheLeaderboard;
use App\Listeners\HandleCheatingDetection;
use App\Listeners\HandleQuizEnd;
use App\Listeners\HandleQuizStart;
use App\Listeners\HandleUserDisconnect;
use App\Listeners\LogUserActivity;
use App\Listeners\NotifyAdmin;
use App\Listeners\SendGoodbyeMessage;
use App\Listeners\SendWelcomeMessage;
use App\Listeners\UpdateLeaderboardListener;
use App\Listeners\UpdateQuizLobby;
use App\Listeners\UpdateQuizStatistics;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        QuizStarted::class => [
            HandleQuizStart::class,
            NotifyAdmin::class . '@handleQuizStarted',
        ],
        
        QuizEnded::class => [
            HandleQuizEnd::class,
            NotifyAdmin::class . '@handleQuizEnded',
        ],
        
        AnswerSubmitted::class => [
            UpdateLeaderboardListener::class,
            UpdateQuizStatistics::class,
            HandleCheatingDetection::class,
        ],
        
        ParticipantJoined::class => [
            SendWelcomeMessage::class,
            UpdateQuizLobby::class . '@handleParticipantJoined',
        ],
        
        ParticipantLeft::class => [
            SendGoodbyeMessage::class,
            UpdateQuizLobby::class . '@handleParticipantLeft',
        ],
        
        UserDisconnected::class => [
            HandleUserDisconnect::class,
        ],
        
        QuestionBroadcasted::class => [
            BroadcastNextQuestion::class,
        ],
        
        LeaderboardUpdated::class => [
            CacheLeaderboard::class,
        ],
        
        Login::class => [
            LogUserActivity::class . '@handleUserLogin',
        ],
        
        Logout::class => [
            LogUserActivity::class . '@handleUserLogout',
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}