@extends('layouts.admin')

@section('title', 'Quiz Participants - ' . $quiz->title)

@section('content')
<style>
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
    }
    .online-indicator {
        width: 8px;
        height: 8px;
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
    .filter-btn {
        margin-right: 10px;
        padding: 8px 20px;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid #dee2e6;
        background: white;
        margin-bottom: 10px;
    }
    .filter-btn.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .filter-btn:hover:not(.active) {
        background-color: #e9ecef;
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
    .participant-row {
        transition: all 0.3s;
    }
    .participant-row:hover {
        background-color: #f8f9fa;
    }
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2><i class="fas fa-users"></i> Quiz Participants</h2>
                <p class="text-muted">{{ $quiz->title }}</p>
            </div>
            <div>
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

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <h3>{{ $activeParticipants }}</h3>
            <p class="mb-0">Active Participants</p>
            <small>(In Lobby + Taking Quiz)</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
            <h3>{{ count($inProgressUsers) }}</h3>
            <p class="mb-0">Taking Quiz Now</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3>{{ $lobbyUsers ?? 0 }}</h3>
            <p class="mb-0">In Lobby</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
            <h3>{{ $completedParticipants }}</h3>
            <p class="mb-0">Completed</p>
        </div>
    </div>
</div>

<!-- Filter Buttons -->
<div class="filter-buttons mb-3">
    <button class="filter-btn active" data-filter="all">All ({{ $participants->count() }})</button>
    <button class="filter-btn" data-filter="active">Active ({{ $activeParticipants }})</button>
    <button class="filter-btn" data-filter="taking">Taking Quiz ({{ count($inProgressUsers) }})</button>
    <button class="filter-btn" data-filter="lobby">In Lobby ({{ $lobbyUsers ?? 0 }})</button>
    <button class="filter-btn" data-filter="completed">Completed ({{ $completedParticipants }})</button>
    <button class="filter-btn" data-filter="left">Left ({{ $participants->where('status', 'left')->count() }})</button>
</div>

<!-- Participants Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Participants List</h5>
    </div>
    <div class="card-body p-0">
        @if($participants->count() > 0)
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
                        @foreach($participants as $index => $participant)
                            @php
                                $isInProgress = in_array($participant->user_id, $inProgressUsers);
                                $hasCompleted = \App\Models\QuizAttempt::where('user_id', $participant->user_id)
                                    ->where('quiz_id', $quiz->id)
                                    ->where('status', 'completed')
                                    ->exists();
                                $userAttempt = \App\Models\QuizAttempt::where('user_id', $participant->user_id)
                                    ->where('quiz_id', $quiz->id)
                                    ->where('status', 'completed')
                                    ->latest()
                                    ->first();
                            @endphp
                            <tr class="participant-row" data-status="{{ $participant->status }}">
                                <td class="text-center">{{ $index + 1 }}    </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            {{ strtoupper(substr($participant->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $participant->user->name }}</strong>
                                            @if($participant->user_id == Auth::id())
                                                <span class="badge bg-info ms-1">You</span>
                                            @endif
                                        </div>
                                    </div>
                                 </td>
                                 <td>
                                    <i class="fas fa-envelope text-muted me-1"></i>
                                    <a href="mailto:{{ $participant->user->email }}">{{ $participant->user->email }}</a>
                                 </td>
                                 <td>
                                    @if($participant->status == 'taking_quiz' || $isInProgress)
                                        <span class="status-badge status-taking-quiz">
                                            <i class="fas fa-play"></i> Taking Quiz
                                            <span class="online-indicator online-indicator-taking ms-1"></span>
                                        </span>
                                    @elseif($participant->status == 'joined')
                                        <span class="status-badge status-active">
                                            <i class="fas fa-circle"></i> In Lobby
                                            <span class="online-indicator online-indicator-active ms-1"></span>
                                        </span>
                                    @elseif($hasCompleted)
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check-circle"></i> Completed
                                        </span>
                                    @elseif($participant->status == 'left')
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
                                    @if($participant->joined_at)
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($participant->joined_at)->format('M d, Y h:i A') }}
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($participant->joined_at)->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                 </td>
                                 <td>
                                    @if($participant->updated_at)
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($participant->updated_at)->diffForHumans() }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                 </td>
                                 <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.users.show', $participant->user_id) }}" 
                                           class="btn btn-sm btn-info" title="View User Profile">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
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
                <p class="text-muted">No one has joined this quiz yet.</p>
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
        // Initialize DataTable only once
        if ($('#participantsTable').length && !$.fn.DataTable.isDataTable('#participantsTable')) {
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
            
            // Redraw table
            const table = $('#participantsTable').DataTable();
            if (table) table.draw();
        });
        
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
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        alert('Quiz link copied to clipboard!');
    }
</script>
@endpush
@endsection