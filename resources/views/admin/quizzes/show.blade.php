@extends('layouts.admin')

@section('title', 'Quiz Details - ' . $quiz->title)

@section('content')
<style>
    .start-quiz-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        transition: all 0.3s ease;
    }
    .start-quiz-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    }
    .stat-card {
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .quiz-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-published {
        background-color: #28a745;
        color: white;
    }
    .status-draft {
        background-color: #6c757d;
        color: white;
    }
    .status-scheduled {
        background-color: #ffc107;
        color: #856404;
    }
    .status-ended {
        background-color: #dc3545;
        color: white;
    }
    .status-live {
        background-color: #17a2b8;
        color: white;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Quiz Details</h5>
                <div>
                    <!-- Start Quiz Button - Only show if quiz is not live yet -->
                    @if((!$quiz->is_published || ($quiz->scheduled_at && $quiz->scheduled_at > now())) && ($stats['participants_waiting'] ?? 0) > 0)
                        <form action="{{ route('admin.quizzes.start', $quiz) }}" method="POST" class="d-inline" id="startQuizForm">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm start-quiz-btn" 
                                    onclick="return confirm('Start this quiz now? Participants in the lobby will be able to take it immediately.')">
                                <i class="fas fa-play"></i> Start Quiz Now
                            </button>
                        </form>
                    @endif
                    
                    @if($quiz->is_published && $quiz->scheduled_at && $quiz->scheduled_at <= now() && (!$quiz->ends_at || $quiz->ends_at > now()))
                        <span class="badge status-live me-2">
                            <i class="fas fa-circle"></i> LIVE NOW
                        </span>
                    @endif
                    
                    <a href="{{ route('admin.quizzes.participants', $quiz) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-users"></i> View Participants
                    </a>
                    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Quiz
                    </a>
                    <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-question-circle"></i> Manage Questions ({{ $quiz->questions->count() }})
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>{{ $quiz->title }}</h4>
                        <p class="text-muted">{{ $quiz->description ?: 'No description provided.' }}</p>
                        @if((!$quiz->is_published || ($quiz->scheduled_at && $quiz->scheduled_at > now())) && ($stats['participants_waiting'] ?? 0) < 1)
                            <div class="alert alert-warning">
                                <i class="fas fa-users"></i> Start Quiz Now will appear after at least 1 participant joins the lobby.
                            </div>
                        @endif
                        
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 150px;">Category:</th>
                                <td>
                                    @if($quiz->category)
                                        @if($quiz->category->icon)
                                            <i class="{{ $quiz->category->icon }}"></i>
                                        @endif
                                        <strong>{{ $quiz->category->name }}</strong>
                                    @else
                                        <span class="text-muted">No Category</span>
                                    @endif
                                </td>
                             </tr>
                            <tr>
                                <th>Duration:</th>
                                <td>{{ $quiz->duration_minutes }} minutes</td>
                             </tr>
                            <tr>
                                <th>Total Questions:</th>
                                <td><span class="badge bg-info">{{ $quiz->questions->count() }}</span></td>
                             </tr>
                            <tr>
                                <th>Total Points:</th>
                                <td><span class="badge bg-success">{{ $quiz->questions->sum('points') }}</span></td>
                             </tr>
                            <tr>
                                <th>Passing Score:</th>
                                <td>{{ $quiz->passing_score }}%</td>
                             </tr>
                            <tr>
                                <th>Max Attempts:</th>
                                <td>{{ $quiz->max_attempts }}</td>
                             </tr>
                            <tr>
                                <th>Random Order:</th>
                                <td>{{ $quiz->is_random_questions ? 'Yes' : 'No' }}</td>
                             </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @php
                                        $statusClass = 'status-draft';
                                        $statusText = 'Draft';
                                        if($quiz->is_published) {
                                            if($quiz->ends_at && $quiz->ends_at < now()) {
                                                $statusClass = 'status-ended';
                                                $statusText = 'Ended';
                                            } elseif($quiz->scheduled_at && $quiz->scheduled_at > now()) {
                                                $statusClass = 'status-scheduled';
                                                $statusText = 'Scheduled';
                                            } elseif($quiz->scheduled_at && $quiz->scheduled_at <= now()) {
                                                $statusClass = 'status-live';
                                                $statusText = 'Live Now';
                                            } else {
                                                $statusClass = 'status-published';
                                                $statusText = 'Published';
                                            }
                                        }
                                    @endphp
                                    <span class="quiz-status {{ $statusClass }}">
                                        @if($statusText == 'Live Now')
                                            <i class="fas fa-circle"></i>
                                        @elseif($statusText == 'Scheduled')
                                            <i class="fas fa-calendar-alt"></i>
                                        @elseif($statusText == 'Ended')
                                            <i class="fas fa-hourglass-end"></i>
                                        @elseif($statusText == 'Published')
                                            <i class="fas fa-check-circle"></i>
                                        @else
                                            <i class="fas fa-pencil-alt"></i>
                                        @endif
                                        {{ $statusText }}
                                    </span>
                                 </td>
                             </tr>
                            @if($quiz->scheduled_at)
                            <tr>
                                <th>Scheduled Start:</th>
                                <td>{{ $quiz->scheduled_at->format('M d, Y h:i A') }}</td>
                             </tr>
                            @endif
                            @if($quiz->ends_at)
                            <tr>
                                <th>Ends At:</th>
                                <td>{{ $quiz->ends_at->format('M d, Y h:i A') }}</td>
                             </tr>
                            @endif
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $quiz->creator->name }} ({{ $quiz->created_at->format('M d, Y') }})</td>
                             </tr>
                         </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-chart-line"></i> Statistics</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-primary text-white stat-card">
                                    <div class="card-body text-center">
                                        <h3>{{ $stats['total_attempts'] }}</h3>
                                        <p class="mb-0">Total Attempts</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-success text-white stat-card">
                                    <div class="card-body text-center">
                                        <h3>{{ number_format($stats['average_score'], 1) }}</h3>
                                        <p class="mb-0">Average Score</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-info text-white stat-card">
                                    <div class="card-body text-center">
                                        <h3>{{ number_format($stats['completion_rate'], 1) }}%</h3>
                                        <p class="mb-0">Completion Rate</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-warning text-white stat-card">
                                    <div class="card-body text-center">
                                        <h3>{{ $stats['total_points'] }}</h3>
                                        <p class="mb-0">Total Points</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats for Live Quiz -->
                        @if($quiz->is_published && (!$quiz->ends_at || $quiz->ends_at > now()))
                            <div class="alert alert-info mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-users fa-2x me-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong>Quiz is {{ $quiz->scheduled_at && $quiz->scheduled_at <= now() ? 'LIVE' : 'Available' }}</strong>
                                        <br>
                                        <small>Share this link with participants:</small>
                                        <div class="input-group mt-2">
                                            <input type="text" class="form-control form-control-sm" id="quizLink" value="{{ url('/user/quiz/lobby/' . $quiz->id) }}" readonly>
                                            <button class="btn btn-sm btn-outline-primary" onclick="copyQuizLink()">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Questions List</h5>
            </div>
            <div class="card-body">
                @if($quiz->questions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Question</th>
                                    <th width="120">Type</th>
                                    <th width="80">Points</th>
                                    <th width="100">Time (sec)</th>
                                    <th width="80">Options</th>
                                    <th width="100">Show Answer</th>
                                    <th width="100">Actions</th>
                                 </thead>
                            <tbody>
                                @foreach($quiz->questions as $index => $question)
                                    <tr>
                                        <td>{{ $index + 1 }} </td>
                                        <td>
                                            <strong>{{ Str::limit($question->question_text, 60) }}</strong>
                                            @if($question->explanation)
                                                <br><small class="text-muted"><i class="fas fa-info-circle"></i> Has explanation</small>
                                            @endif
                                         </td>
                                        <td>
                                            @if($question->question_type == 'multiple_choice')
                                                <span class="badge bg-primary">Multiple Choice</span>
                                            @elseif($question->question_type == 'true_false')
                                                <span class="badge bg-info">True/False</span>
                                            @else
                                                <span class="badge bg-secondary">Single Choice</span>
                                            @endif
                                         </td>
                                        <td><span class="badge bg-success">{{ $question->points }}</span></td>
                                        <td><span class="badge bg-warning">{{ $question->time_seconds }}s</span></td>
                                        <td><span class="badge bg-secondary">{{ $question->options->count() }}</span></td>
                                        <td>
                                            @if($question->show_answer)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-eye"></i> Enabled
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-eye-slash"></i> Disabled
                                                </span>
                                            @endif
                                         </td>
                                        <td>
                                            <a href="{{ route('admin.quizzes.questions.edit', [$quiz, $question]) }}" 
                                               class="btn btn-sm btn-primary" title="Edit Question">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                         </td>
                                     </tr>
                                @endforeach
                            </tbody>
                         </table>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Question
                        </a>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                        <h5>No Questions Yet</h5>
                        <p class="text-muted">Add questions to make this quiz available to users.</p>
                        <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Question
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
    
    // Confirm start quiz
    document.getElementById('startQuizForm')?.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to start this quiz now?\n\nParticipants currently in the lobby will be able to take the quiz immediately.')) {
            e.preventDefault();
        }
    });
</script>
@endpush
@endsection
