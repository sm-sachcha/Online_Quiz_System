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
    .status-joined {
        background-color: #28a745;
        color: white;
    }
    .status-registered {
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
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
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
    .online-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #28a745;
        display: inline-block;
        animation: pulse 1.5s infinite;
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
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-users"></i> Quiz Participants</h2>
                <p class="text-muted">{{ $quiz->title }}</p>
            </div>
            <div>
                <button class="refresh-btn" onclick="window.location.reload()">
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
            <h3>{{ $participants->count() }}</h3>
            <p class="mb-0">Total Participants</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3>{{ $participants->where('status', 'joined')->count() }}</h3>
            <p class="mb-0">Active Now</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
            <h3>{{ $participants->where('status', 'registered')->count() }}</h3>
            <p class="mb-0">Registered</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
            <h3>{{ $participants->where('status', 'completed')->count() }}</h3>
            <p class="mb-0">Completed</p>
        </div>
    </div>
</div>

<!-- Filter Buttons -->
<div class="filter-buttons">
    <button class="filter-btn active" data-filter="all">All ({{ $participants->count() }})</button>
    <button class="filter-btn" data-filter="joined">Active ({{ $participants->where('status', 'joined')->count() }})</button>
    <button class="filter-btn" data-filter="registered">Registered ({{ $participants->where('status', 'registered')->count() }})</button>
    <button class="filter-btn" data-filter="completed">Completed ({{ $participants->where('status', 'completed')->count() }})</button>
    <button class="filter-btn" data-filter="left">Left ({{ $participants->where('status', 'left')->count() }})</button>
</div>

<!-- Participants List -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Participants List</h5>
    </div>
    <div class="card-body">
        @if($participants->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover" id="participantsTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Participant</th>
                            <th>Email</th>
                            <th width="120">Status</th>
                            <th width="180">Joined At</th>
                            <th width="150">Last Activity</th>
                            <th width="100">Actions</th>
                         </thead>
                    <tbody>
                        @foreach($participants as $index => $participant)
                            @php
                                $lastActivity = \App\Models\UserActivity::where('user_id', $participant->user_id)
                                    ->latest()
                                    ->first();
                                $userAttempt = $participant->user->quizAttempts()
                                    ->where('quiz_id', $quiz->id)
                                    ->where('status', 'completed')
                                    ->latest()
                                    ->first();
                            @endphp
                            <tr class="participant-row" data-status="{{ $participant->status }}">
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            {{ strtoupper(substr($participant->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $participant->user->name }}</strong>
                                            @if($participant->status == 'joined')
                                                <br>
                                                <small class="text-success">
                                                    <span class="online-indicator me-1"></span> Online
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-envelope text-muted me-1"></i>
                                    <a href="mailto:{{ $participant->user->email }}">{{ $participant->user->email }}</a>
                                </td>
                                <td>
                                    @if($participant->status == 'joined')
                                        <span class="status-badge status-joined">
                                            <i class="fas fa-circle" style="font-size: 8px;"></i> Active
                                        </span>
                                    @elseif($participant->status == 'registered')
                                        <span class="status-badge status-registered">
                                            <i class="fas fa-clock"></i> Registered
                                        </span>
                                    @elseif($participant->status == 'completed')
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check-circle"></i> Completed
                                        </span>
                                    @elseif($participant->status == 'left')
                                        <span class="status-badge status-left">
                                            <i class="fas fa-sign-out-alt"></i> Left
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($participant->joined_at)
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ $participant->joined_at->format('M d, Y h:i A') }}
                                        <br>
                                        <small class="text-muted">{{ $participant->joined_at->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lastActivity)
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        {{ $lastActivity->created_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.users.show', $participant->user_id) }}" 
                                           class="btn btn-sm btn-info" title="View User Profile">
                                            <i class="fas fa-user"></i>
                                        </a>
                                        @if($userAttempt)
                                            <a href="{{ route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $userAttempt->id]) }}" 
                                               class="btn btn-sm btn-primary" title="View Result" target="_blank">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled title="No completed attempt">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
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
                <p class="text-muted">No one has joined this quiz yet. Share the quiz link to get participants!</p>
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
        // Initialize DataTable
        if ($('#participantsTable').length) {
            if ($.fn.DataTable.isDataTable('#participantsTable')) {
                $('#participantsTable').DataTable().destroy();
            }
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
            
            // Update active button
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            // Filter table rows
            if (filter === 'all') {
                $('.participant-row').show();
            } else {
                $('.participant-row').hide();
                $(`.participant-row[data-status="${filter}"]`).show();
            }
            
            // Redraw DataTable
            const table = $('#participantsTable').DataTable();
            table.draw();
        });
    });
    
    function copyQuizLink() {
        const linkInput = document.getElementById('quizLink');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        // Show notification
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i> Quiz link copied to clipboard!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Auto-refresh every 15 seconds
    let autoRefresh = setInterval(function() {
        location.reload();
    }, 15000);
    
    window.addEventListener('beforeunload', function() {
        clearInterval(autoRefresh);
    });
</script>
@endpush
@endsection