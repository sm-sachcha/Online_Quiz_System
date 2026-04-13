<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - ' . config('app.name'))</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Pusher JS for WebSocket -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <!-- Laravel Echo -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
    <style>
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        #sidebar {
            min-width: 280px;
            max-width: 280px;
            background: #2c3e50;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar.active {
            margin-left: -280px;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #1a2632;
            text-align: center;
        }
        #sidebar ul.components {
            padding: 20px 0;
        }
        #sidebar ul li a {
            padding: 12px 20px;
            display: block;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        #sidebar ul li a:hover {
            background: #1a2632;
            padding-left: 30px;
        }
        #sidebar ul li.active > a {
            background: #1a2632;
            border-left: 4px solid #3498db;
        }
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        #sidebar ul ul a {
            padding-left: 50px;
            font-size: 0.9em;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -280px;
            }
            #sidebar.active {
                margin-left: 0;
            }
        }
        .handle {
            cursor: move;
        }
        .handle i {
            color: #6c757d;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h4>{{ config('app.name') }}</h4>
                <span class="badge bg-info mt-2">{{ ucfirst(Auth::user()->role) }}</span>
            </div>

            <ul class="list-unstyled components">
                <!-- DYNAMIC DASHBOARD LINK BASED ON USER ROLE -->
                @if(Auth::user()->isMasterAdmin())
                    <li class="{{ request()->routeIs('master-admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('master-admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                @else
                    <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                @endif
                
                <!-- Categories - Available to both Admin and Master Admin -->
                <li class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.categories.index') }}">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                
                <!-- Quizzes - Available to both Admin and Master Admin -->
                <li class="{{ request()->routeIs('admin.quizzes.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.quizzes.index') }}">
                        <i class="fas fa-question-circle"></i> Quizzes
                    </a>
                </li>

                <!-- Results - Available to both Admin and Master Admin -->
                <li class="{{ request()->routeIs('admin.results.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.results.index') }}">
                        <i class="fas fa-chart-line"></i> Results
                    </a>
                </li>
                
                <!-- Users - Available to both Admin and Master Admin -->
                <li class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                
                <!-- Reports - Available to both Admin and Master Admin -->
                <li>
                    <a href="#reportsSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-chart-bar"></i> Reports <i class="fas fa-chevron-down float-end"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="reportsSubmenu">
                        <li><a href="{{ route('admin.reports.quiz-performance') }}">Quiz Performance</a></li>
                        <li><a href="{{ route('admin.reports.user-activity') }}">User Activity</a></li>
                        <li><a href="{{ route('admin.reports.system-overview') }}">System Overview</a></li>
                    </ul>
                </li>

                <!-- Master Admin Only Section -->
                @if(Auth::user()->isMasterAdmin())
                    <li class="mt-3">
                        <hr class="bg-light">
                        <small class="text-muted ms-3">MASTER ADMIN</small>
                    </li>
                    <li class="{{ request()->routeIs('master-admin.admins.*') ? 'active' : '' }}">
                        <a href="{{ route('master-admin.admins.index') }}">
                            <i class="fas fa-user-tie"></i> Manage Admins
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('master-admin.settings.*') ? 'active' : '' }}">
                        <a href="{{ route('master-admin.settings.index') }}">
                            <i class="fas fa-cog"></i> System Settings
                        </a>
                    </li>
                @endif
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> {{ Auth::user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="fas fa-user"></i> Profile
                                </a></li>
                                
                                @if(Auth::user()->isMasterAdmin())
                                    <li><a class="dropdown-item" href="{{ route('master-admin.dashboard') }}">
                                        <i class="fas fa-crown"></i> Master Admin Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-chart-line"></i> General Admin Panel
                                    </a></li>
                                @else
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-home"></i> Admin Dashboard
                                    </a></li>
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
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
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

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
            
            $('.datatable').DataTable({
                pageLength: 25,
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });
            
            $('.delete-btn').on('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
        
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

        window.initializeEcho = function(quizId, callbacks) {
            const echoInstance = window.resolveEchoInstance();

            if (!quizId || !echoInstance || typeof echoInstance.channel !== 'function') {
                return null;
            }

            const channelName = 'quiz.' + quizId;

            try {
                echoInstance.leaveChannel(channelName);
            } catch (e) {
                // channel not yet subscribed
            }

            const channel = echoInstance.channel(channelName);

            if (callbacks) {
                if (callbacks.onParticipantJoined) {
                    channel.listen('.participant.joined', callbacks.onParticipantJoined);
                }
                if (callbacks.onParticipantLeft) {
                    channel.listen('.participant.left', callbacks.onParticipantLeft);
                }
                if (callbacks.onLobbyUpdated) {
                    channel.listen('.lobby.updated', callbacks.onLobbyUpdated);
                }
                if (callbacks.onParticipantsUpdated) {
                    channel.listen('.participants.updated', callbacks.onParticipantsUpdated);
                }
                if (callbacks.onQuizStarted) {
                    channel.listen('.quiz.started', callbacks.onQuizStarted);
                }
                if (callbacks.onQuizEnded) {
                    channel.listen('.quiz.ended', callbacks.onQuizEnded);
                }
            }

            return channel;
        };
    </script>
    @stack('scripts')
</body>
</html>
