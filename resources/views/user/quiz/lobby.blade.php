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
    .online-indicator-taking {
        background-color: #ffc107;
        animation: none;
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
    .join-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
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
    .resume-btn {
        background-color: #ffc107;
        color: #856404;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }
    .resume-btn:hover {
        background-color: #e0a800;
        transform: scale(1.02);
    }
    .waiting-btn {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: not-allowed;
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
    .public-banner {
        background-color: #d4edda;
        border-left: 4px solid #28a745;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    .guest-badge {
        background-color: #17a2b8;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }
    .taking-quiz-badge {
        background-color: #ffc107;
        color: #856404;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }
    .login-prompt {
        background-color: #e7f1ff;
        border: 1px solid #b6d4fe;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .guest-input-group {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    .guest-input {
        flex: 1;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
    }
    .guest-input:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 2px rgba(40,167,69,0.1);
    }
    .guest-join-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: all 0.3s;
    }
    .guest-join-btn:hover {
        background-color: #218838;
        transform: scale(1.02);
    }
    .joined-info {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        text-align: center;
        animation: fadeIn 0.5s ease;
    }
    .joined-name {
        font-weight: bold;
        color: #155724;
        font-size: 18px;
    }
    .current-user {
        background-color: #e8f5e9 !important;
        border-left: 3px solid #4caf50 !important;
    }
    .taking-quiz-user {
        background-color: #fff3cd !important;
        border-left: 3px solid #ffc107 !important;
    }
    .countdown-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    }
    .countdown-container {
        text-align: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 60px;
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        animation: bounceIn 0.5s ease;
    }
    .countdown-number {
        font-size: 120px;
        font-weight: bold;
        font-family: monospace;
        color: white;
        text-shadow: 0 5px 20px rgba(0,0,0,0.3);
        line-height: 1;
    }
    .countdown-label {
        font-size: 24px;
        color: white;
        margin-top: 20px;
        letter-spacing: 2px;
    }
    @keyframes bounceIn {
        0% { transform: scale(0.3); opacity: 0; }
        50% { transform: scale(1.05); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); opacity: 1; }
    }
    .quiz-started-warning {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin-top: 20px;
    }
    .leave-loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #fff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
        margin-right: 5px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .attempt-history {
        max-height: 200px;
        overflow-y: auto;
    }
    .attempt-item {
        font-size: 13px;
        padding: 8px 12px;
        border-bottom: 1px solid #eee;
    }
    .attempt-item:last-child {
        border-bottom: none;
    }
    .attempt-passed {
        border-left: 3px solid #28a745;
    }
    .attempt-failed {
        border-left: 3px solid #dc3545;
    }
    .attempt-in-progress {
        border-left: 3px solid #ffc107;
    }
</style>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-gamepad"></i> {{ $quiz->title }}</h4>
            </div>
            <div class="card-body">
                @if(isset($isPublicQuiz) && $isPublicQuiz)
                    <div class="public-banner">
                        <i class="fas fa-globe text-success"></i>
                        <strong>Public Quiz!</strong> Everyone can participate.
                    </div>
                @endif
                
                @if(isset($quizStartedByAdmin) && $quizStartedByAdmin && empty($inProgressAttempt) && empty($participant))
                    <div class="quiz-started-warning">
                        <i class="fas fa-ban fa-2x text-danger mb-2"></i>
                        <h4 class="text-danger">Joining Closed</h4>
                        <p class="mb-0">This quiz has already started, so new participants can no longer join.</p>
                        <!-- <a href="{{ route('user.dashboard') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-home"></i> Back to Dashboard
                        </a> -->
                    </div>
                @endif
                
                <p class="lead">{{ $quiz->description ?? 'No description available.' }}</p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle text-primary"></i> Quiz Details</h6>
                        <ul class="list-unstyled">
                            <!-- <li><i class="fas fa-tag text-primary"></i> Category: <strong>{{ $quiz->category->name ?? 'Uncategorized' }}</strong></li>
                            <li><i class="far fa-clock text-primary"></i> Duration: <strong>{{ $quiz->duration_minutes }} minutes</strong></li> -->
                            <li><i class="fas fa-question-circle text-primary"></i> Questions: <strong>{{ $quiz->total_questions }}</strong></li>
                            <li><i class="fas fa-star text-primary"></i> Total Points: <strong>{{ $quiz->total_points }}</strong></li>
                            <li><i class="fas fa-check-circle text-primary"></i> Passing Score: <strong>{{ $quiz->passing_score }}%</strong></li>
                            <!-- <li><i class="fas fa-redo text-primary"></i> Max Attempts: <strong>{{ $quiz->max_attempts }}</strong></li> -->
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6><i class="fas fa-gavel text-warning"></i> Rules</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock text-warning"></i> Time limit per question</li>
                            <!-- <li><i class="fas fa-ban text-warning"></i> No tab switching during quiz</li>
                            <li><i class="fas fa-trophy text-warning"></i> Points awarded for correct answers</li> -->
                            @if($quiz->is_random_questions)
                                <li><i class="fas fa-random text-warning"></i> Questions are randomized</li>
                            @endif
                            @if($quiz->is_random_options)
                                <li><i class="fas fa-random text-warning"></i> Options are randomized</li>
                            @endif
                        </ul>
                    </div>
                </div>

                @if(isset($inProgressAttempt) && $inProgressAttempt)
                    <div class="alert alert-warning mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-hourglass-half"></i>
                                <strong>You have an unfinished attempt!</strong>
                                <div class="mt-1">
                                    <small>Started: {{ $inProgressAttempt->started_at->format('M d, Y h:i A') }}</small>
                                    <br>
                                    <small>Progress: {{ $inProgressAttempt->answers()->count() }} of {{ $quiz->total_questions }} questions answered</small>
                                </div>
                            </div>
                            <a href="{{ route('user.quiz.attempt', ['quiz' => $quiz->id, 'attempt' => $inProgressAttempt->id]) }}" 
                               class="resume-btn">
                                <i class="fas fa-play"></i> Resume Quiz
                            </a>
                        </div>
                    </div>
                @endif

                @if(Auth::check() && isset($attemptHistory) && $attemptHistory->count() > 0)
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-history"></i>
                        <strong>Your Attempt History:</strong>
                        <div class="attempt-history mt-2">
                            @foreach($attemptHistory as $attempt)
                                <div class="attempt-item attempt-{{ $attempt->status == 'completed' ? ($attempt->result && $attempt->result->passed ? 'passed' : 'failed') : 'in-progress' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Attempt #{{ $loop->iteration }}</strong>
                                            <span class="badge {{ $attempt->status == 'completed' ? ($attempt->result && $attempt->result->passed ? 'bg-success' : 'bg-danger') : 'bg-warning' }} ms-2">
                                                {{ $attempt->status == 'completed' ? ($attempt->result && $attempt->result->passed ? 'Passed' : 'Failed') : 'In Progress' }}
                                            </span>
                                        </div>
                                        <div>
                                            <small>Score: {{ $attempt->score }}/{{ $quiz->total_points }}</small>
                                            <small class="text-muted ms-2">{{ $attempt->created_at->format('M d, Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Attempts used: {{ $completedAttemptsCount ?? 0 }} of {{ $quiz->max_attempts }}
                                @if(($remainingAttempts = $quiz->max_attempts - ($completedAttemptsCount ?? 0)) > 0)
                                    <span class="text-success">({{ $remainingAttempts }} remaining)</span>
                                @else
                                    <span class="text-danger">(No attempts left)</span>
                                @endif
                            </small>
                        </div>
                    </div>
                @endif

                @if($quiz->scheduled_at && $quiz->scheduled_at > now())
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-calendar-alt"></i> 
                        <strong>Scheduled:</strong> {{ $quiz->scheduled_at->format('F j, Y g:i A') }} - 
                        {{ $quiz->ends_at->format('F j, Y g:i A') }}
                    </div>
                @endif

                @if(isset($requiresLogin) && $requiresLogin && !Auth::check())
                    <div class="login-prompt mt-4">
                        <i class="fas fa-lock fa-2x text-primary mb-3"></i>
                        <h5>This quiz requires login</h5>
                        <p>Please login or register to participate in this quiz.</p>
                        <div class="mt-3">
                            <a href="{{ route('login') }}" class="btn btn-primary me-2">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-center mt-4">
                        <div id="lateJoinClosedNotice" class="quiz-started-warning" style="display: none;">
                            <i class="fas fa-lock fa-2x text-danger mb-2"></i>
                            <h5 class="text-danger mb-2">Joining is closed</h5>
                            <p class="mb-0">The admin has already started this quiz. New users cannot join now.</p>
                        </div>

                        @if(Auth::check())
                            @php
                                $remainingAttempts = $remainingAttempts ?? ($quiz->max_attempts - ($completedAttemptsCount ?? 0));
                            @endphp
                            @if($remainingAttempts <= 0 && $quiz->max_attempts > 0)
                                <button class="btn btn-secondary btn-lg" disabled>
                                    <i class="fas fa-ban"></i> No Attempts Left
                                </button>
                            @elseif(isset($inProgressAttempt) && $inProgressAttempt)
                                <!-- Already showing resume button above -->
                            @elseif(isset($participant) && $participant && $participant->status === 'joined')
                                @if(isset($quizStartedByAdmin) && $quizStartedByAdmin)
                                    <p class="text-muted mt-2 small">The quiz is live. You will be redirected automatically.</p>
                                @else
                                    <button class="waiting-btn" disabled>
                                        <i class="fas fa-hourglass-half"></i> Waiting for Quiz to Start...
                                    </button>
                                    <p class="text-muted mt-2 small">The quiz will start when the administrator clicks "Start Quiz"</p>
                                @endif
                            @else
                                @if(isset($quizStartedByAdmin) && $quizStartedByAdmin)
                                    <!-- Joining is closed once the quiz starts -->
                                @else
                                    <button class="join-btn" id="joinQuizBtn">
                                        <i class="fas fa-sign-in-alt"></i> Join Lobby
                                    </button>
                                @endif
                            @endif
                        @elseif(isset($isPublicQuiz) && $isPublicQuiz)
                            <div class="mt-4 p-4 bg-light rounded" id="guestJoinSection" style="{{ (isset($quizStartedByAdmin) && $quizStartedByAdmin && empty($participant) && empty($inProgressAttempt)) ? 'display: none;' : '' }}">
                                <h5><i class="fas fa-user-plus"></i> Join the Quiz</h5>
                                <div class="guest-input-group">
                                    <input type="text" id="guestNameInput" class="guest-input" placeholder="Enter your name" autocomplete="off">
                                    <button id="directJoinBtn" class="guest-join-btn">
                                        <i class="fas fa-sign-in-alt"></i> Join Lobby
                                    </button>
                                </div>
                            </div>
                            <div id="joinedInfo" class="joined-info" style="display: none;">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <h5>You have joined as:</h5>
                                <p class="joined-name" id="joinedName"></p>
                                <button class="btn btn-warning" id="leaveLobbyBtn">
                                    <i class="fas fa-sign-out-alt"></i> Leave Lobby
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> Participants
                </h5>
                <span class="count-badge" id="participantCount">0</span>
            </div>
            <div class="card-body p-0">
                <div id="participantsList" class="list-group list-group-flush">
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Loading participants...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const quizId = {{ $quiz->id }};
    const userId = {{ Auth::id() ?? 0 }};
    const currentUserName = @json(Auth::user()?->name);
    let isQuizStarted = {{ isset($quizStartedByAdmin) && $quizStartedByAdmin ? 'true' : 'false' }};
    const initialParticipantId = {{ isset($participant) && $participant ? $participant->id : 'null' }};
    const initialParticipantStatus = @json(isset($participant) && $participant ? $participant->status : null);
    const initialGuestName = @json(session('guest_name'));
    let participants = @json($participants->values());
    const joinUrl = `/user/quiz/lobby/${quizId}/join`;
    const leaveUrl = `/user/quiz/lobby/${quizId}/leave`;
    const heartbeatUrl = `/user/quiz/lobby/${quizId}/heartbeat`;
    const participantsUrl = `/user/quiz/lobby/${quizId}/participants`;
    const statusUrl = `/user/quiz/${quizId}/status`;
    let hasLeft = false;
    let isJoined = false;
    let redirectInterval = null;
    let isRedirecting = false;
    let currentParticipantId = null;
    let channel = null;
    let heartbeatInterval = null;
    let participantsPollInterval = null;
    let statusPollInterval = null;
    let leaveSignalSent = false;
    let participantsSnapshot = '';
    const csrfToken = '{{ csrf_token() }}';

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(options.headers || {})
            },
            ...options
        });

        const contentType = response.headers.get('content-type') || '';
        const rawBody = await response.text();
        const data = contentType.includes('application/json') && rawBody
            ? JSON.parse(rawBody)
            : {};

        if (!response.ok) {
            throw new Error(data.error || data.message || `Request failed with status ${response.status}`);
        }

        return data;
    }

    function initRealtimeChannel() {
        if (channel || typeof window.initializeEcho !== 'function') {
            return;
        }

        try {
            channel = window.initializeEcho(quizId, {
                onParticipantJoined(event) {
                    const participant = event.participant;

                    if (!participant) {
                        return;
                    }

                    upsertParticipant(participant);
                    showNotification(`${participant.name} joined the lobby!`, 'info');
                },
                onParticipantLeft(event) {
                    const participant = event.participant;

                    if (!participant) {
                        return;
                    }

                    removeParticipant(participant);
                    showNotification(`${participant.name} left the lobby`, 'warning');
                },
                onLobbyUpdated(event) {
                    if (Array.isArray(event.participants)) {
                        participants = event.participants;
                        renderParticipants();
                    }
                },
                onQuizStarted(event) {
                    isQuizStarted = true;
                    if (shouldAutoStartQuiz()) {
                        const redirectUrl = event.redirect_url || `/user/quiz/start/${quizId}`;
                        showNotification('Quiz is starting now!', 'success');
                        setTimeout(() => beginQuizStartRedirect(redirectUrl), 300);
                    } else {
                        updateLobbyEntryState();
                    }
                },
                onQuizEnded() {
                    showNotification('This quiz has ended.', 'warning');
                    setTimeout(() => window.location.reload(), 1200);
                },
                onParticipantsUpdated(event) {
                    const payload = event.payload || {};
                    if (Array.isArray(payload.lobby_participants)) {
                        participants = payload.lobby_participants;
                        renderParticipants();
                    }
                }
            });
        } catch (error) {
            channel = null;
            console.warn('Realtime channel initialization failed:', error);
        }
    }

    function updateLobbyEntryState() {
        const joinBtn = document.querySelector('.join-btn');
        const guestSection = document.getElementById('guestJoinSection');
        const lateJoinClosedNotice = document.getElementById('lateJoinClosedNotice');

        if (!isQuizStarted || hasActiveLobbyIdentity()) {
            return;
        }

        if (joinBtn) {
            joinBtn.style.display = 'none';
        }

        if (guestSection) {
            guestSection.style.display = 'none';
        }

        if (lateJoinClosedNotice) {
            lateJoinClosedNotice.style.display = 'block';
        }
    }

    function hasActiveLobbyIdentity() {
        return Boolean(
            isJoined ||
            currentParticipantId ||
            initialParticipantId ||
            sessionStorage.getItem('joined_quiz_' + quizId) === 'true'
        );
    }

    function shouldAutoStartQuiz() {
        return hasActiveLobbyIdentity() && !hasLeft && !isRedirecting;
    }

    function buildParticipantsSnapshot(items) {
        return JSON.stringify((items || []).map((participant) => ({
            id: participant.id || null,
            user_id: participant.user_id || null,
            name: participant.name || '',
            is_guest: !!participant.is_guest,
            status: participant.status || 'joined',
        })));
    }

    function upsertParticipant(participant) {
        const nextParticipant = {
            status: 'joined',
            ...participant,
        };

        const participantIndex = participants.findIndex((item) => {
            if (nextParticipant.id && item.id === nextParticipant.id) return true;
            if (nextParticipant.user_id && item.user_id === nextParticipant.user_id) return true;
            return false;
        });

        if (participantIndex === -1) {
            participants.push(nextParticipant);
        } else {
            participants.splice(participantIndex, 1, {
                ...participants[participantIndex],
                ...nextParticipant,
            });
        }

        renderParticipants();
    }

    function removeParticipant(participant) {
        participants = participants.filter((item) => {
            if (participant.id && item.id === participant.id) return false;
            if (participant.user_id && item.user_id === participant.user_id) return false;
            return true;
        });

        renderParticipants();
    }

    function getParticipantKey(participant) {
        if (participant.id) return `participant:${participant.id}`;
        if (participant.user_id) return `user:${participant.user_id}`;
        return `name:${participant.name || 'guest'}`;
    }

    function buildParticipantMarkup(participant) {
        const isCurrentUser = (userId && participant.user_id === userId) ||
            (currentParticipantId && participant.id === currentParticipantId) ||
            (participant.name === sessionStorage.getItem('guest_name_' + quizId));
        const isGuest = participant.is_guest || false;
        const isTakingQuiz = participant.status === 'taking_quiz';

        return `
            <div class="avatar-sm me-3">
                ${(participant.name || '?').charAt(0).toUpperCase()}
            </div>
            <div class="flex-grow-1">
                <strong>${escapeHtml(participant.name)}</strong>
                ${isGuest ? '<span class="guest-badge">Participant</span>' : ''}
                ${isTakingQuiz ? '<span class="taking-quiz-badge ms-1"><i class="fas fa-play"></i> Taking Quiz</span>' : ''}
                ${isCurrentUser ? '<br><small class="text-muted">You</small>' : ''}
            </div>
            <div>
                <span class="online-indicator ${isTakingQuiz ? 'online-indicator-taking' : ''}"></span>
            </div>
        `;
    }

    function redirectToQuizStart() {
        if (isRedirecting) return;
        stopLobbyHeartbeat();
        isRedirecting = true;
        leaveSignalSent = true;
        window.location.href = `/user/quiz/start/${quizId}`;
    }

    function beginQuizStartRedirect(redirectUrl = null) {
        if (isRedirecting) return;
        stopLobbyHeartbeat();
        isRedirecting = true;
        leaveSignalSent = true;
        window.showQuizStartCountdown(3, redirectUrl || `/user/quiz/start/${quizId}`);
    }

    function startLobbyHeartbeat() {
        if (heartbeatInterval) {
            return;
        }

        heartbeatInterval = setInterval(() => {
            if (!isJoined || hasLeft || isRedirecting) {
                return;
            }

            requestJson(heartbeatUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                }
            }).catch(() => {});
        }, 15000);
    }

    function stopLobbyHeartbeat() {
        if (!heartbeatInterval) {
            return;
        }

        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }

    function refreshParticipants() {
        requestJson(participantsUrl)
            .then((data) => {
                if (Array.isArray(data)) {
                    const nextSnapshot = buildParticipantsSnapshot(data);
                    if (nextSnapshot === participantsSnapshot) {
                        return;
                    }

                    participants = data;
                    participantsSnapshot = nextSnapshot;
                    renderParticipants();
                }
            })
            .catch((error) => {
                console.warn('Participants refresh failed:', error.message);
            });
    }

    function startParticipantsPolling() {
        if (participantsPollInterval) {
            return;
        }

        participantsPollInterval = setInterval(() => {
            if (document.hidden || isRedirecting) {
                return;
            }

            refreshParticipants();
        }, 2000);
    }

    function stopParticipantsPolling() {
        if (!participantsPollInterval) {
            return;
        }

        clearInterval(participantsPollInterval);
        participantsPollInterval = null;
    }

    function syncQuizStartStatus() {
        requestJson(statusUrl)
            .then((data) => {
                if (data.is_started && !isQuizStarted) {
                    isQuizStarted = true;
                    updateLobbyEntryState();

                    if (shouldAutoStartQuiz()) {
                        beginQuizStartRedirect(`/user/quiz/start/${quizId}`);
                    }
                }
            })
            .catch((error) => {
                console.warn('Quiz status refresh failed:', error.message);
            });
    }

    function startStatusPolling() {
        if (statusPollInterval) {
            return;
        }

        statusPollInterval = setInterval(() => {
            if (document.hidden || isRedirecting) {
                return;
            }

            syncQuizStartStatus();
        }, 2000);
    }

    function stopStatusPolling() {
        if (!statusPollInterval) {
            return;
        }

        clearInterval(statusPollInterval);
        statusPollInterval = null;
    }

    function notifyLobbyLeave() {
        if (!isJoined || hasLeft || isRedirecting || leaveSignalSent) {
            return;
        }

        leaveSignalSent = true;
        stopLobbyHeartbeat();

        const leaveData = new FormData();
        leaveData.append('_token', csrfToken);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(leaveUrl, leaveData);
            return;
        }

        fetch(leaveUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            keepalive: true
        }).catch(() => {});
    }

    function joinLobby(guestName = null) {
        if (isQuizStarted && !hasActiveLobbyIdentity()) {
            alert('The quiz has already started. New participants cannot join now.');
            return;
        }

        const data = {};
        if (guestName) data.guest_name = guestName;
        
        const joinBtn = document.getElementById('directJoinBtn');
        const regularJoinBtn = document.getElementById('joinQuizBtn');
        const guestInput = document.getElementById('guestNameInput');
        const guestSection = document.getElementById('guestJoinSection');
        const joinedInfoDiv = document.getElementById('joinedInfo');
        
        const btnToDisable = joinBtn || regularJoinBtn;
        
        if (btnToDisable) {
            btnToDisable.disabled = true;
            btnToDisable.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining...';
        }
        if (guestInput) guestInput.disabled = true;
        
        requestJson(joinUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(data => {
            if (data.success) {
                isJoined = true;
                hasLeft = false;
                leaveSignalSent = false;
                currentParticipantId = data.participant_id;
                
                if (guestSection) guestSection.style.display = 'none';
                if (joinedInfoDiv) {
                    if (guestName) document.getElementById('joinedName').innerText = guestName;
                    joinedInfoDiv.style.display = 'block';
                }
                
                sessionStorage.setItem('joined_quiz_' + quizId, 'true');
                if (guestName) sessionStorage.setItem('guest_name_' + quizId, guestName);
                if (currentParticipantId) sessionStorage.setItem('participant_id_' + quizId, currentParticipantId);

                upsertParticipant({
                    id: currentParticipantId,
                    user_id: userId || null,
                    name: guestName || currentUserName || 'You',
                    is_guest: !userId,
                    status: 'joined'
                });
                initRealtimeChannel();
                startLobbyHeartbeat();
                startParticipantsPolling();
                startStatusPolling();
                refreshParticipants();
                
                if (isQuizStarted) {
                    redirectToQuizStart();
                }
                
                showNotification('Successfully joined the lobby!', 'success');
            } else {
                alert(data.error || 'Failed to join lobby. Please try again.');
                if (btnToDisable) {
                    btnToDisable.disabled = false;
                    btnToDisable.innerHTML = guestName ? 'Join Lobby' : '<i class="fas fa-sign-in-alt"></i> Join Lobby';
                }
                if (guestInput) guestInput.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error joining:', error);
            alert(error.message || 'Failed to join lobby. Please try again.');
            if (btnToDisable) {
                btnToDisable.disabled = false;
                btnToDisable.innerHTML = guestName ? 'Join Lobby' : '<i class="fas fa-sign-in-alt"></i> Join Lobby';
            }
            if (guestInput) guestInput.disabled = false;
        });
    }

    function leaveLobby() {
        if (hasLeft) return;
        
        const leaveBtn = document.getElementById('leaveLobbyBtn');
        if (leaveBtn) {
            leaveBtn.disabled = true;
            leaveBtn.innerHTML = '<span class="leave-loading"></span> Leaving...';
        }
        
        requestJson(leaveUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(data => {
            if (data.success) {
                stopLobbyHeartbeat();
                stopParticipantsPolling();
                stopStatusPolling();
                hasLeft = true;
                leaveSignalSent = true;
                isJoined = false;
                sessionStorage.removeItem('joined_quiz_' + quizId);
                sessionStorage.removeItem('guest_name_' + quizId);
                sessionStorage.removeItem('participant_id_' + quizId);
                removeParticipant({ id: currentParticipantId, user_id: userId || null });
                window.location.reload();
            } else {
                alert(data.message || 'Failed to leave lobby. Please try again.');
                if (leaveBtn) {
                    leaveBtn.disabled = false;
                    leaveBtn.innerHTML = 'Leave Lobby';
                }
            }
        })
        .catch(error => {
            console.error('Error leaving lobby:', error);
            alert(error.message || 'Failed to leave lobby. Please try again.');
            if (leaveBtn) {
                leaveBtn.disabled = false;
                leaveBtn.innerHTML = 'Leave Lobby';
            }
        });
    }

    function renderParticipants() {
        const list = document.getElementById('participantsList');
        if (!list) return;
        
        if (!participants || participants.length === 0) {
            participantsSnapshot = buildParticipantsSnapshot([]);
            list.innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-user-friends"></i> No active participants</div>';
            document.getElementById('participantCount').textContent = '0';
            return;
        }

        participantsSnapshot = buildParticipantsSnapshot(participants);

        const existingItems = new Map();
        Array.from(list.querySelectorAll('[data-participant-key]')).forEach((node) => {
            existingItems.set(node.dataset.participantKey, node);
        });

        const orderedNodes = participants.map((participant) => {
            const key = getParticipantKey(participant);
            const isCurrentUser = (userId && participant.user_id === userId) ||
                (currentParticipantId && participant.id === currentParticipantId) ||
                (participant.name === sessionStorage.getItem('guest_name_' + quizId));
            const isTakingQuiz = participant.status === 'taking_quiz';
            let item = existingItems.get(key);

            if (!item) {
                item = document.createElement('div');
                item.dataset.participantKey = key;
            }

            item.className = 'list-group-item participant-item d-flex align-items-center';
            if (isCurrentUser) item.classList.add('current-user');
            if (isTakingQuiz) item.classList.add('taking-quiz-user');

            const nextMarkup = buildParticipantMarkup(participant);
            if (item.innerHTML !== nextMarkup) {
                item.innerHTML = nextMarkup;
            }

            existingItems.delete(key);
            return item;
        });

        existingItems.forEach((node) => node.remove());
        list.replaceChildren(...orderedNodes);
        
        document.getElementById('participantCount').textContent = participants.length;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '250px';
        notification.style.animation = 'slideIn 0.3s ease';
        notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i> ${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    function checkIfAlreadyJoined() {
        const joined = sessionStorage.getItem('joined_quiz_' + quizId);
        if (joined === 'true') {
            isJoined = true;
            currentParticipantId = sessionStorage.getItem('participant_id_' + quizId);
            const guestName = sessionStorage.getItem('guest_name_' + quizId);
            if (guestName && document.getElementById('joinedName')) {
                document.getElementById('joinedName').innerText = guestName;
                const guestSection = document.getElementById('guestJoinSection');
                const joinedInfoDiv = document.getElementById('joinedInfo');
                if (guestSection) guestSection.style.display = 'none';
                if (joinedInfoDiv) joinedInfoDiv.style.display = 'block';
            }
            startLobbyHeartbeat();
            startParticipantsPolling();
            startStatusPolling();
            initRealtimeChannel();
            if (isQuizStarted) {
                redirectToQuizStart();
            }
            return;
        }

        if (initialParticipantId && ['joined', 'taking_quiz'].includes(initialParticipantStatus)) {
            isJoined = true;
            currentParticipantId = initialParticipantId;

            sessionStorage.setItem('joined_quiz_' + quizId, 'true');
            sessionStorage.setItem('participant_id_' + quizId, String(initialParticipantId));

            if (initialGuestName) {
                sessionStorage.setItem('guest_name_' + quizId, initialGuestName);
            }

            const joinedNameEl = document.getElementById('joinedName');
            const guestSection = document.getElementById('guestJoinSection');
            const joinedInfoDiv = document.getElementById('joinedInfo');

            if (joinedNameEl && initialGuestName) {
                joinedNameEl.innerText = initialGuestName;
            }

            if (guestSection && initialGuestName) {
                guestSection.style.display = 'none';
            }

            if (joinedInfoDiv && initialGuestName) {
                joinedInfoDiv.style.display = 'block';
            }

            startLobbyHeartbeat();
            startParticipantsPolling();
            startStatusPolling();

            initRealtimeChannel();

            if (isQuizStarted) {
                redirectToQuizStart();
            }
        }
    }

    // Event Listeners
    const directJoinBtn = document.getElementById('directJoinBtn');
    if (directJoinBtn) {
        directJoinBtn.addEventListener('click', function() {
            const guestName = document.getElementById('guestNameInput').value.trim();
            if (!guestName) {
                alert('Please enter your name');
                return;
            }
            if (guestName.length < 2) {
                alert('Name must be at least 2 characters long');
                return;
            }
            joinLobby(guestName);
        });
        
        const guestInput = document.getElementById('guestNameInput');
        if (guestInput) {
            guestInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const guestName = this.value.trim();
                    if (guestName) joinLobby(guestName);
                    else alert('Please enter your name');
                }
            });
        }
    }

    const joinBtn = document.getElementById('joinQuizBtn');
    if (joinBtn) {
        joinBtn.addEventListener('click', function() { joinLobby(); });
    }

    const leaveLobbyBtn = document.getElementById('leaveLobbyBtn');
    if (leaveLobbyBtn) {
        leaveLobbyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to leave the lobby?')) {
                leaveLobby();
            }
        });
    }

    // Initialize
    checkIfAlreadyJoined();
    renderParticipants();
    initRealtimeChannel();
    refreshParticipants();
    syncQuizStartStatus();
    startParticipantsPolling();
    startStatusPolling();
    
    window.addEventListener('beforeunload', function() {
        if (redirectInterval) clearInterval(redirectInterval);
        stopParticipantsPolling();
        stopStatusPolling();
        stopLobbyHeartbeat();
    });

    window.addEventListener('pagehide', function() {
        stopParticipantsPolling();
        stopStatusPolling();
        stopLobbyHeartbeat();
    });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshParticipants();
            syncQuizStartStatus();
        }
    });
</script>
@endpush
@endsection
