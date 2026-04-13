<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Online Quiz System'))</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Pusher JS -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <!-- Laravel Echo -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        #app {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        main {
            flex: 1 0 auto;
        }
        
        footer {
            flex-shrink: 0;
            margin-top: auto;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .quiz-card {
            transition: transform 0.3s;
        }
        
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .timer {
            font-size: 1.5rem;
            font-weight: bold;
            font-family: monospace;
        }
        
        .option-btn {
            transition: all 0.3s;
            white-space: normal;
            text-align: left;
            padding: 15px;
        }
        
        .option-btn:hover:not(:disabled) {
            transform: translateX(5px);
        }
        
        .leaderboard-item {
            transition: all 0.3s;
        }
        
        .leaderboard-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .timer-danger {
            animation: pulse 1s infinite;
            background-color: #dc3545 !important;
        }
        
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .content-wrapper {
            flex: 1;
        }
        
        /* Quiz start countdown overlay */
        .quiz-start-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .countdown-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .countdown-number {
            font-size: 72px;
            font-weight: bold;
            color: white;
            font-family: monospace;
        }
        
        .countdown-label {
            font-size: 24px;
            color: white;
            margin-top: 20px;
        }
        
        .countdown-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            margin-top: 10px;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-graduation-cap"></i> {{ config('app.name', 'Online Quiz System') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            <!-- </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">
                                    <i class="fas fa-user-plus"></i> Register
                                </a>
                            </li> -->
                        @else
                            @if(Auth::user()->isUser())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('user.dashboard') }}">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                            @elseif(Auth::user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                            @elseif(Auth::user()->isMasterAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('master-admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                            @endif
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i> {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                                            <i class="fas fa-user"></i> My Profile
                                        </a>
                                    </li>
                                    @if(Auth::user()->isUser())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('user.results') }}">
                                            <i class="fas fa-chart-line"></i> My Results
                                        </a>
                                    </li>
                                    @endif
                                    @if(Auth::user()->isAdmin())
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                                <i class="fas fa-tachometer-alt"></i> Admin Panel
                                            </a>
                                        </li>
                                    @endif
                                    @if(Auth::user()->isMasterAdmin())
                                        <li>
                                            <a class="dropdown-item" href="{{ route('master-admin.dashboard') }}">
                                                <i class="fas fa-crown"></i> Master Admin Panel
                                            </a>
                                        </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="fas fa-sign-out-alt"></i> Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <div class="container py-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Footer - Always at Bottom -->
        <footer class="bg-light text-center text-lg-start border-top">
            <div class="text-center p-3">
                <i class="fas fa-copyright"></i> {{ date('Y') }} Online Quiz System. Design by Sachcha.
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Show loading spinner
        window.showLoading = function() {
            let spinner = document.createElement('div');
            spinner.className = 'spinner-overlay';
            spinner.innerHTML = '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>';
            document.body.appendChild(spinner);
            return spinner;
        };
        
        window.hideLoading = function(spinner) {
            if (spinner) spinner.remove();
        };
        
        window.resolveEchoInstance = function() {
            const existingInstance = window.__quizEchoInstance
                || (window.Echo && typeof window.Echo.channel === 'function' ? window.Echo : null);

            if (existingInstance) {
                return existingInstance;
            }

            const EchoConstructor =
                (typeof window.Echo === 'function' ? window.Echo : null)
                || (window.Echo && typeof window.Echo.default === 'function' ? window.Echo.default : null)
                || (typeof window.LaravelEcho === 'function' ? window.LaravelEcho : null)
                || (window.LaravelEcho && typeof window.LaravelEcho.default === 'function' ? window.LaravelEcho.default : null)
                || (typeof Echo === 'function' ? Echo : null);

            if (!EchoConstructor || typeof Pusher === 'undefined') {
                return null;
            }

            const configuredHost = '{{ env('VITE_REVERB_HOST', env('REVERB_HOST', '127.0.0.1')) }}';
            const websocketHost = ['127.0.0.1', 'localhost', '0.0.0.0'].includes(configuredHost)
                ? window.location.hostname
                : configuredHost;

            window.__quizEchoInstance = new EchoConstructor({
                broadcaster: 'reverb',
                key: '{{ env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY')) }}',
                wsHost: websocketHost,
                wsPort: Number('{{ env('VITE_REVERB_PORT', env('REVERB_PORT', 8080)) }}'),
                wssPort: Number('{{ env('VITE_REVERB_PORT', env('REVERB_PORT', 8080)) }}'),
                forceTLS: '{{ env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'http')) }}' === 'https',
                authEndpoint: '/broadcasting/auth',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
            });

            window.Echo = window.__quizEchoInstance;

            return window.__quizEchoInstance;
        };

        // Initialize the shared quiz channel with Laravel Echo / Reverb.
        window.initializeEcho = function(quizId, callbacks) {
            const echoInstance = window.resolveEchoInstance();

            if (!quizId || !echoInstance || typeof echoInstance.channel !== 'function') {
                return null;
            }

            if (window.__quizEchoChannelName) {
                const configuredHost = '{{ env('VITE_REVERB_HOST', env('REVERB_HOST', '127.0.0.1')) }}';
                try {
                    echoInstance.leaveChannel(window.__quizEchoChannelName);
                } catch (error) {
                    console.debug('Echo channel reset skipped', error);
                }
            }

            const channelName = `quiz.${quizId}`;
            const channel = echoInstance.channel(channelName);
            window.__quizEchoChannelName = channelName;

            if (callbacks) {
                if (callbacks.onQuizStarted) {
                    channel.listen('.quiz.started', callbacks.onQuizStarted);
                }
                if (callbacks.onQuizEnded) {
                    channel.listen('.quiz.ended', callbacks.onQuizEnded);
                }
                if (callbacks.onLeaderboardUpdated) {
                    channel.listen('.leaderboard.updated', callbacks.onLeaderboardUpdated);
                }
                if (callbacks.onParticipantJoined) {
                    channel.listen('.participant.joined', callbacks.onParticipantJoined);
                }
                if (callbacks.onParticipantLeft) {
                    channel.listen('.participant.left', callbacks.onParticipantLeft);
                }
                if (callbacks.onLobbyUpdated) {
                    channel.listen('.lobby.updated', callbacks.onLobbyUpdated);
                }
                if (callbacks.onQuestionBroadcasted) {
                    channel.listen('.question.broadcasted', callbacks.onQuestionBroadcasted);
                }
                if (callbacks.onTimerSynced) {
                    channel.listen('.timer.synced', callbacks.onTimerSynced);
                }
                if (callbacks.onAnswerSubmitted) {
                    channel.listen('.answer.submitted', callbacks.onAnswerSubmitted);
                }
                if (callbacks.onUserDisconnected) {
                    channel.listen('.user.disconnected', callbacks.onUserDisconnected);
                }
                if (callbacks.onParticipantsUpdated) {
                    channel.listen('.participants.updated', callbacks.onParticipantsUpdated);
                }
            }

            return channel;
        };

        window.initializeAttemptEcho = function(attemptId, callbacks) {
            const echoInstance = window.resolveEchoInstance();

            if (!attemptId || !echoInstance || typeof echoInstance.channel !== 'function') {
                return null;
            }

            const channelName = `quiz.attempt.${attemptId}`;

            try {
                echoInstance.leaveChannel(channelName);
            } catch (error) {
                console.debug('Attempt Echo channel reset skipped', error);
            }

            const channel = echoInstance.channel(channelName);

            if (callbacks) {
                if (callbacks.onAttemptQuestionBroadcasted) {
                    channel.listen('.attempt.question.broadcasted', callbacks.onAttemptQuestionBroadcasted);
                }
                if (callbacks.onAttemptResultUpdated) {
                    channel.listen('.attempt.result.updated', callbacks.onAttemptResultUpdated);
                }
            }

            return channel;
        };
        
        // Show countdown when quiz starts
        window.showQuizStartCountdown = function(seconds, redirectUrl) {
            let countdown = seconds;
            
            const overlay = document.createElement('div');
            overlay.className = 'quiz-start-overlay';
            overlay.innerHTML = `
                <div class="countdown-box">
                    <div class="countdown-number" id="countdownNumber">${countdown}</div>
                    <div class="countdown-label">Quiz Starting Soon!</div>
                    <div class="countdown-sub">Get ready...</div>
                </div>
            `;
            document.body.appendChild(overlay);
            
            const interval = setInterval(() => {
                countdown--;
                const numEl = document.getElementById('countdownNumber');
                if (numEl) numEl.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(interval);
                    overlay.remove();
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }
            }, 1000);
            
            return interval;
        };
        
        // Show notification
        window.showNotification = function(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.style.animation = 'slideIn 0.3s ease';
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        };
        
        // Add slideIn animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
    @stack('scripts')
</body>
</html>
