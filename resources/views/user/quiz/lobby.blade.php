@extends('layouts.app')

@section('title', 'Quiz Lobby - ' . $quiz->title)

@section('content')
<style>
    .participant-item {
        transition: all 0.3s ease;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .online-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #28a745;
        display: inline-block;
        margin-right: 5px;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
        100% { opacity: 1; transform: scale(1); }
    }
    .avatar-sm {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    .join-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
        font-weight: bold;
    }
    .join-btn:hover {
        background-color: #218838;
        transform: scale(1.02);
    }
    .start-btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
        font-weight: bold;
    }
    .start-btn:hover {
        background-color: #0056b3;
        transform: scale(1.02);
    }
    .dashboard-btn {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }
    .dashboard-btn:hover {
        background-color: #5a6268;
        transform: scale(1.02);
        color: white;
        text-decoration: none;
    }
    .count-badge {
        background-color: #fff;
        color: #007bff;
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: bold;
    }
    .warning-banner {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
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
    .rank-card {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        border: none;
    }
    .rank-number {
        font-size: 48px;
        font-weight: bold;
    }
    .medal-gold { color: #ffd700; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
    .medal-silver { color: #c0c0c0; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
    .medal-bronze { color: #cd7f32; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
</style>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-gamepad"></i> {{ $quiz->title }}</h4>
            </div>
            <div class="card-body">
                <!-- Warning Banner -->
                <div class="warning-banner">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <strong>Note:</strong> You will be automatically removed from the lobby when you leave this page.
                </div>
                
                <p class="lead">{{ $quiz->description ?? 'No description available.' }}</p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle text-primary"></i> Quiz Details</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-tag text-primary"></i> Category: <strong>{{ $quiz->category->name }}</strong></li>
                            <li><i class="far fa-clock text-primary"></i> Duration: <strong>{{ $quiz->duration_minutes }} minutes</strong></li>
                            <li><i class="fas fa-question-circle text-primary"></i> Questions: <strong>{{ $quiz->total_questions }}</strong></li>
                            <li><i class="fas fa-star text-primary"></i> Total Points: <strong>{{ $quiz->total_points }}</strong></li>
                            <li><i class="fas fa-check-circle text-primary"></i> Passing Score: <strong>{{ $quiz->passing_score }}%</strong></li>
                            <li><i class="fas fa-redo text-primary"></i> Max Attempts: <strong>{{ $quiz->max_attempts }}</strong></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6><i class="fas fa-gavel text-warning"></i> Rules</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock text-warning"></i> Time limit per question</li>
                            <li><i class="fas fa-ban text-warning"></i> No tab switching during quiz</li>
                            <li><i class="fas fa-trophy text-warning"></i> Points awarded for correct answers</li>
                            @if($quiz->is_random_questions)
                                <li><i class="fas fa-random text-warning"></i> Questions are randomized</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Attempts Information Section -->
                @if(isset($hasAttemptsLeft) && isset($remainingAttempts))
                    <div class="alert alert-info mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle"></i> 
                                <strong>Attempts Information:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Maximum Attempts: <strong>{{ $quiz->max_attempts }}</strong></li>
                                    <li>Attempts Used: <strong>{{ $completedAttempts ?? 0 }}</strong></li>
                                    <li>Remaining Attempts: <strong>{{ $remainingAttempts }}</strong></li>
                                </ul>
                            </div>
                            @if(isset($inProgressAttempt) && $inProgressAttempt)
                                <a href="{{ route('user.quiz.attempt', ['quiz' => $quiz->id, 'attempt' => $inProgressAttempt->id]) }}" 
                                   class="btn btn-warning">
                                    <i class="fas fa-play"></i> Resume Attempt
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if(isset($hasAttemptsLeft) && !$hasAttemptsLeft)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>No attempts remaining!</strong> You have used all {{ $quiz->max_attempts }} attempts for this quiz.
                    </div>
                @endif

                @if($quiz->scheduled_at)
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-calendar-alt"></i> 
                        <strong>Scheduled:</strong> {{ $quiz->scheduled_at->format('F j, Y g:i A') }} - 
                        {{ $quiz->ends_at->format('F j, Y g:i A') }}
                    </div>
                @endif

                <div class="text-center mt-4">
                    @if(isset($hasAttemptsLeft) && !$hasAttemptsLeft)
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-ban"></i> No Attempts Left
                        </button>
                        <a href="{{ route('user.quiz.attempts', $quiz) }}" class="btn btn-info btn-lg ms-2">
                            <i class="fas fa-history"></i> View My Attempts ({{ $completedAttempts }})
                        </a>
                    @elseif(isset($inProgressAttempt) && $inProgressAttempt)
                        <a href="{{ route('user.quiz.attempt', ['quiz' => $quiz->id, 'attempt' => $inProgressAttempt->id]) }}" 
                           class="start-btn">
                            <i class="fas fa-play"></i> Resume Quiz
                        </a>
                        <a href="{{ route('user.dashboard') }}" class="dashboard-btn ms-2">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    @elseif($participant->status === 'joined')
                        <button class="start-btn" id="startQuizBtn">
                            <i class="fas fa-play"></i> Start New Attempt
                        </button>
                        <a href="{{ route('user.dashboard') }}" class="dashboard-btn ms-2">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    @else
                        <button class="join-btn" id="joinQuizBtn">
                            <i class="fas fa-sign-in-alt"></i> Join Lobby
                        </button>
                        <a href="{{ route('user.dashboard') }}" class="dashboard-btn ms-2">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    @endif
                    
                    @if(isset($completedAttempts) && $completedAttempts > 0)
                        <a href="{{ route('user.quiz.attempts', $quiz) }}" class="btn btn-info btn-lg ms-2">
                            <i class="fas fa-history"></i> View My Attempts ({{ $completedAttempts }})
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Active Participants Card -->
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> Active Participants 
                    <span class="count-badge float-end" id="participantCount">0</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div id="participantsList" class="list-group list-group-flush">
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Loading active participants...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rank Card -->
        @if(isset($userRank) && $userRank)
            <div class="card mt-3 shadow rank-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Your Best Rank</h5>
                </div>
                <div class="card-body text-center">
                    <div class="rank-number mb-2">
                        @if($userRank == 1)
                            <span class="medal-gold">🥇 1st</span>
                        @elseif($userRank == 2)
                            <span class="medal-silver">🥈 2nd</span>
                        @elseif($userRank == 3)
                            <span class="medal-bronze">🥉 3rd</span>
                        @else
                            <span class="text-dark">#{{ $userRank }}</span>
                        @endif
                    </div>
                    <h5>{{ $userRank == 1 ? 'Champion!' : ($userRank == 2 ? 'Runner Up!' : ($userRank == 3 ? 'Third Place!' : $userRank . 'th Place')) }}</h5>
                    <p class="text-muted">Best Score: <strong class="text-success">{{ $bestScore ?? 0 }}</strong> points</p>
                    @if(isset($totalParticipants) && $totalParticipants)
                        <p class="text-muted mb-1">Out of <strong>{{ $totalParticipants }}</strong> participants</p>
                        <div class="progress mt-2" style="height: 8px;">
                            @php
                                $percentile = $totalParticipants > 0 ? (($userRank - 1) / $totalParticipants) * 100 : 0;
                                $topPercent = 100 - $percentile;
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $topPercent }}%"></div>
                        </div>
                        <small class="text-muted">Top {{ round($topPercent, 1) }}% of participants</small>
                    @endif
                </div>
            </div>
        @else
            <div class="card mt-3 shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Your Rank</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-hourglass-half fa-3x text-muted mb-2"></i>
                    <p class="text-muted">Complete the quiz to see your rank!</p>
                    @if(isset($hasAttemptsLeft) && $hasAttemptsLeft && $participant->status === 'joined')
                        <a href="{{ route('user.quiz.start', $quiz) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-play"></i> Take Quiz
                        </a>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- Tips Section -->
        <div class="card mt-3 shadow">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-clock text-info"></i> Each question has a time limit</li>
                    <li class="mb-2"><i class="fas fa-chart-line text-success"></i> Score is calculated based on correct answers</li>
                    <li class="mb-2"><i class="fas fa-trophy text-warning"></i> Compete with others on the leaderboard</li>
                    <li><i class="fas fa-eye-slash text-danger"></i> Tab switching is monitored</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    const quizId = {{ $quiz->id }};
    const userId = {{ Auth::id() }};
    let participants = [];
    let hasLeft = false;
    let heartbeatInterval = null;
    let reconnectAttempts = 0;

    // Function to leave the lobby
    function leaveLobby() {
        if (hasLeft) return;
        hasLeft = true;
        
        console.log('Leaving lobby...');
        
        fetch(`/user/quiz/lobby/${quizId}/leave`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            keepalive: true
        }).catch(console.error);
    }

    // Send heartbeat to keep connection alive
    function sendHeartbeat() {
        if (hasLeft) return;
        
        fetch(`/user/quiz/lobby/${quizId}/heartbeat`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Heartbeat sent successfully');
                reconnectAttempts = 0;
            } else {
                console.log('Heartbeat failed');
                reconnectAttempts++;
                if (reconnectAttempts > 5) {
                    console.log('Too many failed heartbeats, leaving lobby');
                    leaveLobby();
                }
            }
        })
        .catch(error => {
            console.error('Heartbeat error:', error);
            reconnectAttempts++;
            if (reconnectAttempts > 5) {
                leaveLobby();
            }
        });
    }

    // Start heartbeat interval (every 20 seconds)
    function startHeartbeat() {
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        heartbeatInterval = setInterval(sendHeartbeat, 20000);
        sendHeartbeat(); // Send immediately
    }

    function stopHeartbeat() {
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
    }

    // Handle page unload - only leave when actually leaving the page
    window.addEventListener('beforeunload', function() {
        const participantStatus = '{{ $participant->status }}';
        if (participantStatus === 'joined' && !hasLeft) {
            leaveLobby();
        }
        stopHeartbeat();
    });
    
    // Handle page visibility change - don't leave on tab switch
    let inactivityTimer = null;
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Tab is hidden, but don't leave immediately
            // Just slow down heartbeat
            if (heartbeatInterval) {
                clearInterval(heartbeatInterval);
                heartbeatInterval = setInterval(sendHeartbeat, 30000);
            }
        } else {
            // Tab is active again, resume normal heartbeat
            if (heartbeatInterval) {
                clearInterval(heartbeatInterval);
                heartbeatInterval = setInterval(sendHeartbeat, 20000);
            }
            // Send immediate heartbeat to confirm still active
            sendHeartbeat();
            // Refresh participants list
            loadParticipants();
        }
    });

    // Initialize Pusher
    const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            }
        }
    });

    const channel = pusher.subscribe('presence-quiz.' + quizId);

    channel.bind('participant.joined', function(data) {
        console.log('Participant joined:', data);
        addParticipant(data.user);
    });

    channel.bind('participant.left', function(data) {
        console.log('Participant left:', data);
        removeParticipant(data.user.id);
    });

    function loadParticipants() {
        fetch(`/user/quiz/lobby/${quizId}/participants`, {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Loaded participants:', data);
            participants = data;
            renderParticipants();
        })
        .catch(error => {
            console.error('Error loading participants:', error);
        });
    }

    function renderParticipants() {
        const list = document.getElementById('participantsList');
        if (!list) return;
        
        if (!participants || participants.length === 0) {
            list.innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-user-friends"></i> No active participants</div>';
            document.getElementById('participantCount').textContent = '0';
            return;
        }
        
        list.innerHTML = '';
        participants.forEach(participant => {
            const isYou = participant.id === userId;
            const isTakingQuiz = participant.status === 'taking_quiz' || participant.is_active === true;
            
            const item = document.createElement('div');
            item.className = 'list-group-item participant-item';
            if (isYou) {
                item.classList.add('bg-light', 'border-primary');
            }
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        ${participant.name.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-grow-1">
                        <strong>${escapeHtml(participant.name)}</strong>
                        ${isYou ? '<br><small class="text-muted">You</small>' : ''}
                        ${isTakingQuiz ? '<br><small class="text-warning"><i class="fas fa-play"></i> Taking quiz</small>' : ''}
                    </div>
                    <div>
                        <span class="online-indicator ${isTakingQuiz ? 'bg-warning' : 'bg-success'}"></span>
                    </div>
                </div>
            `;
            list.appendChild(item);
        });
        
        document.getElementById('participantCount').textContent = participants.length;
    }

    function addParticipant(user) {
        const exists = participants.find(p => p.id === user.id);
        if (!exists) {
            participants.push(user);
            renderParticipants();
            if (user.id !== userId) {
                showNotification(`${user.name} joined the lobby`, 'success');
            }
        }
    }

    function removeParticipant(userIdToRemove) {
        const participant = participants.find(p => p.id === userIdToRemove);
        if (participant) {
            participants = participants.filter(p => p.id !== userIdToRemove);
            renderParticipants();
            if (participant.id !== userId) {
                showNotification(`${participant.name} left the lobby`, 'info');
            }
        }
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '250px';
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Join button handler
    const joinBtn = document.getElementById('joinQuizBtn');
    if (joinBtn) {
        joinBtn.addEventListener('click', function() {
            fetch(`/user/quiz/lobby/${quizId}/join`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error joining:', error);
                alert('Failed to join lobby. Please try again.');
            });
        });
    }

    // Start quiz button handler
    const startBtn = document.getElementById('startQuizBtn');
    if (startBtn) {
        startBtn.addEventListener('click', function() {
            window.location.href = `/user/quiz/start/${quizId}`;
        });
    }

    // Load initial participants
    loadParticipants();
    
    // Start heartbeat
    startHeartbeat();
    
    // Refresh participants every 10 seconds
    setInterval(function() {
        if (!hasLeft && !document.hidden) {
            loadParticipants();
        }
    }, 10000);
</script>
@endpush
@endsection