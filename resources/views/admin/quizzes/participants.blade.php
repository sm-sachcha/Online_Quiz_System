@extends('layouts.admin')

@section('title', 'Quiz Participants - ' . $quiz->title)

@section('content')
<style>
    .participant-card {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    .participant-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .status-active {
        background-color: #28a745;
        color: white;
    }
    .status-taking-quiz {
        background-color: #ffc107;
        color: #856404;
    }
    .status-left {
        background-color: #dc3545;
        color: white;
    }
    .status-completed {
        background-color: #17a2b8;
        color: white;
    }
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
        flex-shrink: 0;
    }
    .avatar-guest {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        transition: transform 0.3s;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .refresh-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .refresh-btn:hover {
        background-color: #218838;
        transform: scale(1.02);
    }
    .start-quiz-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        transition: all 0.3s;
        font-weight: bold;
    }
    .start-quiz-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    }
    .quit-quiz-btn {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        transition: all 0.3s;
        font-weight: bold;
    }
    .quit-quiz-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220,53,69,0.3);
    }
    .online-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 1.5s infinite;
    }
    .online-indicator-active {
        background-color: #28a745;
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
    .filter-buttons {
        margin-bottom: 20px;
    }
    .filter-btn {
        margin-right: 10px;
        padding: 8px 20px;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid #dee2e6;
        background: white;
    }
    .filter-btn.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .filter-btn:hover:not(.active) {
        background-color: #e9ecef;
    }
    .guest-badge {
        background-color: #17a2b8;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }
    .quiz-status-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .participant-name {
        font-weight: 600;
    }
    .last-active-time {
        font-size: 12px;
        color: #6c757d;
    }
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2><i class="fas fa-users"></i> Quiz Participants</h2>
                <p class="text-muted">{{ $quiz->title }}</p>
            </div>
            <div class="d-flex gap-2">
                @if(!isset($isQuizStarted) || !$isQuizStarted)
                    @if(isset($hasQuestions) && $hasQuestions)
                        <button type="button" class="btn btn-success btn-lg start-quiz-btn" id="startQuizBtn">
                            <i class="fas fa-play"></i> Start Quiz Now
                        </button>
                    @endif
                @else
                    <button type="button" class="btn btn-danger btn-lg quit-quiz-btn" id="quitQuizBtn">
                        <i class="fas fa-stop"></i> End Quiz
                    </button>
                @endif
                
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Back to Quiz
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quiz Status Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card quiz-status-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <i class="fas fa-info-circle"></i> <strong>Quiz Status:</strong>
                        @if(isset($isQuizStarted) && $isQuizStarted)
                            <span class="badge bg-success ms-2">ACTIVE</span>
                            <div class="mt-1">
                                <small>Quiz started at: {{ $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at)->format('h:i A') : 'Just now' }}</small>
                            </div>
                        @if($isQuizStarted)
                            <div class="alert alert-warning">
                                <i class="fas fa-play-circle"></i> 
                                <strong>Quiz is started!</strong>
                            </div>
                        @endif
                        @else
                            <span class="badge bg-secondary ms-2">WAITING</span>
                            <div class="mt-1">
                                <small>Waiting for participants to join</small>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-users"></i> <strong>Participants in Lobby:</strong>
                        <h3 class="mb-0 mt-1">{{ $lobbyUsers ?? 0 }}</h3>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-question-circle"></i> <strong>Questions:</strong>
                        <h3 class="mb-0 mt-1">{{ isset($hasQuestions) && $hasQuestions ? $quiz->questions()->count() : 0 }}</h3>
                        @if(!isset($hasQuestions) || !$hasQuestions)
                            <small class="text-warning">Add questions to start</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards - Using Controller Variables -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3 id="activeParticipantsCount">{{ $activeParticipants ?? 0 }}</h3>
            <p class="mb-0">Active Participants</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
            <h3 id="takingQuizCount">{{ count($inProgressUsers ?? []) }}</h3>
            <p class="mb-0">Taking Quiz Now</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3 id="lobbyCount">{{ $lobbyUsers ?? 0 }}</h3>
            <p class="mb-0">In Lobby</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
            <h3 id="completedCount">{{ $completedParticipants ?? 0 }}</h3>
            <p class="mb-0">Completed</p>
        </div>
    </div>
</div>

<!-- Filter Buttons - Using Controller Variables -->
<div class="filter-buttons">
    <button class="filter-btn active" data-filter="all">All (<span id="filterAllCount">{{ count($participants ?? []) }}</span>)</button>
    <button class="filter-btn" data-filter="active">Active (<span id="filterActiveCount">{{ $activeParticipants ?? 0 }}</span>)</button>
    <button class="filter-btn" data-filter="taking">Taking Quiz (<span id="filterTakingCount">{{ count($inProgressUsers ?? []) }}</span>)</button>
    <button class="filter-btn" data-filter="lobby">In Lobby (<span id="filterLobbyCount">{{ $lobbyUsers ?? 0 }}</span>)</button>
    <button class="filter-btn" data-filter="completed">Completed (<span id="filterCompletedCount">{{ $completedParticipants ?? 0 }}</span>)</button>
    <button class="filter-btn" data-filter="left">Left (<span id="filterLeftCount">{{ $leftParticipants ?? 0 }}</span>)</button>
</div>

<!-- Participants Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Participants List</h5>
    </div>
    <div class="card-body p-0">
        @if(isset($participants) && count($participants) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="participantsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Participant</th>
                            <th>Email</th>
                            <th width="140">Status</th>
                            <th width="180">Joined At</th>
                            <th width="150">Last Active</th>
                            <th width="100">Actions</th>
                        </thead>
                    <tbody>
                        @php
                            // Convert inProgressUsers to array if it's a Collection
                            $inProgressArray = [];
                            if (isset($inProgressUsers)) {
                                if ($inProgressUsers instanceof \Illuminate\Database\Eloquent\Collection) {
                                    $inProgressArray = $inProgressUsers->toArray();
                                } elseif (is_array($inProgressUsers)) {
                                    $inProgressArray = $inProgressUsers;
                                }
                            }
                        @endphp
                        @foreach($participants as $index => $participant)
                            @php
                                $participantId = $participant['id'] ?? null;
                                $participantUserId = $participant['user_id'] ?? null;
                                $participantName = $participant['name'] ?? 'Unknown';
                                $isGuest = $participant['is_guest'] ?? false;
                                $participantStatus = $participant['status'] ?? 'unknown';
                                $joinedAt = $participant['joined_at'] ?? null;
                                $updatedAt = $participant['updated_at'] ?? null;
                                
                                $isInProgress = false;
                                if ($participantUserId && in_array($participantUserId, $inProgressArray)) {
                                    $isInProgress = true;
                                }
                                
                                $displayEmail = $isGuest ? 'N/A' : ($participant['email'] ?? 'N/A');
                                
                                $hasCompleted = false;
                                if ($participantUserId) {
                                    $hasCompleted = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)
                                        ->where('user_id', $participantUserId)
                                        ->where('status', 'completed')
                                        ->exists();
                                } elseif ($participantId) {
                                    $hasCompleted = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)
                                        ->where('participant_id', $participantId)
                                        ->where('status', 'completed')
                                        ->exists();
                                }
                                
                                $userAttempt = null;
                                if ($participantUserId) {
                                    $userAttempt = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)
                                        ->where('user_id', $participantUserId)
                                        ->where('status', 'completed')
                                        ->latest()
                                        ->first();
                                } elseif ($participantId) {
                                    $userAttempt = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)
                                        ->where('participant_id', $participantId)
                                        ->where('status', 'completed')
                                        ->latest()
                                        ->first();
                                }
                                
                                $avatarLetter = $participantName ? strtoupper(substr($participantName, 0, 1)) : '?';
                                $avatarClass = $isGuest ? 'avatar-guest' : '';
                            @endphp
                            <tr class="participant-row" data-status="{{ $participantStatus }}">
                                <td class="text-center">{{ $index + 1 }} </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3 {{ $avatarClass }}">
                                            {{ $avatarLetter }}
                                        </div>
                                        <div>
                                            <span class="participant-name">{{ $participantName }}</span>
                                            @if($isGuest)
                                                <span class="guest-badge">Examinee</span>
                                            @endif
                                            @if($participantUserId == Auth::id())
                                                <span class="badge bg-info ms-1">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas {{ $isGuest ? 'fa-user-friends' : 'fa-envelope' }} text-muted me-1"></i>
                                    {{ $displayEmail }}
                                </td>
                                <td>
                                    @if($participantStatus == 'taking_quiz' || $isInProgress)
                                        <span class="status-badge status-taking-quiz">
                                            <i class="fas fa-play"></i> Taking Quiz
                                            <span class="online-indicator online-indicator-taking ms-1"></span>
                                        </span>
                                    @elseif($participantStatus == 'joined')
                                        <span class="status-badge status-active">
                                            <i class="fas fa-circle"></i> In Lobby
                                            <span class="online-indicator online-indicator-active ms-1"></span>
                                        </span>
                                    @elseif($hasCompleted)
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check-circle"></i> Completed
                                        </span>
                                    @elseif($participantStatus == 'left')
                                        <span class="status-badge status-left">
                                            <i class="fas fa-sign-out-alt"></i> Left
                                        </span>
                                    @else
                                        <span class="status-badge status-left">
                                            <i class="fas fa-clock"></i> Registered
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($joinedAt)
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($joinedAt)->format('M d, Y h:i A') }}
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($joinedAt)->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($updatedAt)
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        <span class="last-active-time">{{ \Carbon\Carbon::parse($updatedAt)->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if($userAttempt)
                                            <a href="{{ route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $userAttempt->id]) }}" 
                                               class="btn btn-sm btn-primary" title="View Result" target="_blank">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-user-friends fa-4x text-muted mb-3"></i>
                <h5>No Participants Yet</h5>
                <p class="text-muted">No one has joined this quiz yet. Share the link to get participants!</p>
                <div class="mt-3">
                    <div class="input-group w-50 mx-auto">
                        <input type="text" class="form-control" id="quizLink" value="{{ url('/user/quiz/lobby/' . $quiz->id) }}" readonly>
                        <button class="btn btn-primary" onclick="copyQuizLink()">
                            <i class="fas fa-copy"></i> Copy Link
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const quizId = {{ $quiz->id }};
        
        // Initialize DataTable only if table exists and not already initialized
        if ($('#participantsTable').length && $('#participantsTable tbody tr').length > 0 && !$.fn.DataTable.isDataTable('#participantsTable')) {
            $('#participantsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[4, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ participants",
                    emptyTable: "No participants found"
                },
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });
        }
        
        // Filter functionality
        $('.filter-btn').click(function() {
            const filter = $(this).data('filter');
            
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            if (filter === 'all') {
                $('.participant-row').show();
            } else {
                $('.participant-row').hide();
                $('.participant-row').each(function() {
                    const statusCell = $(this).find('td:eq(3)');
                    const statusText = statusCell.text();
                    
                    if (filter === 'active' && (statusText.includes('In Lobby') || statusText.includes('Taking Quiz'))) {
                        $(this).show();
                    } else if (filter === 'taking' && statusText.includes('Taking Quiz')) {
                        $(this).show();
                    } else if (filter === 'lobby' && statusText.includes('In Lobby')) {
                        $(this).show();
                    } else if (filter === 'completed' && statusText.includes('Completed')) {
                        $(this).show();
                    } else if (filter === 'left' && (statusText.includes('Left') || statusText.includes('Registered'))) {
                        $(this).show();
                    }
                });
            }
            
            const table = $('#participantsTable').DataTable();
            if (table) table.draw();
        });
        
        // Start Quiz Button Handler
        const startQuizBtn = document.getElementById('startQuizBtn');
        if (startQuizBtn) {
            startQuizBtn.addEventListener('click', function() {
                const originalText = startQuizBtn.innerHTML;
                startQuizBtn.disabled = true;
                startQuizBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting Quiz...';
                
                fetch(`/admin/quizzes/${quizId}/start`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Quiz started successfully! ' + (data.participants_notified || 0) + ' participants notified.');
                        location.reload();
                    } else {
                        alert('' + (data.error || 'Failed to start quiz. Please try again.'));
                        startQuizBtn.disabled = false;
                        startQuizBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error. Please try again.');
                    startQuizBtn.disabled = false;
                    startQuizBtn.innerHTML = originalText;
                });
            });
        }
        
        // Quit Quiz Button Handler
        const quitQuizBtn = document.getElementById('quitQuizBtn');
        if (quitQuizBtn) {
            quitQuizBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to end this quiz? Participants will no longer be able to take it.')) {
                    const originalText = quitQuizBtn.innerHTML;
                    quitQuizBtn.disabled = true;
                    quitQuizBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ending Quiz...';
                    
                    fetch(`/admin/quizzes/${quizId}/quit`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Quiz ended successfully.');
                            location.reload();
                        } else {
                            alert('' + (data.error || 'Failed to end quiz. Please try again.'));
                            quitQuizBtn.disabled = false;
                            quitQuizBtn.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Network error. Please try again.');
                        quitQuizBtn.disabled = false;
                        quitQuizBtn.innerHTML = originalText;
                    });
                }
            });
        }
        
        // Auto-refresh every 10 seconds
        let autoRefresh = setInterval(function() {
            location.reload();
        }, 10000);
        
        window.addEventListener('beforeunload', function() {
            clearInterval(autoRefresh);
        });
    });
    
    function copyQuizLink() {
        const linkInput = document.getElementById('quizLink');
        if (linkInput) {
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
            alert('✅ Quiz link copied to clipboard!');
        }
    }
</script>
@endpush
@endsection