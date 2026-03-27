@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
<style>
    .stat-card {
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .quiz-card {
        transition: all 0.3s ease;
    }
    .quiz-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .see-more-btn {
        background-color: #6c757d;
        color: white;
        transition: all 0.3s ease;
    }
    .see-more-btn:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }
    .more-quizzes {
        display: none;
    }
    .more-quizzes.show {
        display: block;
    }
    .more-categories {
        display: none;
    }
    .more-categories.show {
        display: block;
    }
    .more-scheduled {
        display: none;
    }
    .more-scheduled.show {
        display: block;
    }
    .category-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .category-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .category-icon {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 20px;
    }
    .equal-height-card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .equal-height-card .card-body {
        flex: 1;
        overflow-y: auto;
    }
    .featured-list, .attempts-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .scheduled-quiz {
        transition: all 0.3s ease;
    }
    .countdown-timer {
        font-family: monospace;
        font-weight: bold;
        color: #856404;
    }
    .quiz-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    .badge-scheduled {
        background-color: #ffc107;
        color: #856404;
    }
    .badge-active {
        background-color: #28a745;
        color: white;
    }
    .badge-ended {
        background-color: #dc3545;
        color: white;
    }
    
    .schedule-info {
        background-color: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 12px;
    }
</style>

<div id="dashboardContent">
    <div class="row">
        <div class="col-md-12">
            <!-- Welcome message - ONLY shows on login, NOT on refresh -->
            @if(session('show_welcome') && session('welcome_message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-smile-wink"></i> {{ session('welcome_message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @php
                    // Clear the welcome message after displaying
                    session()->forget('show_welcome');
                    session()->forget('welcome_message');
                @endphp
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Quizzes Taken</h6>
                            <h2 class="mb-0">{{ $stats['total_quizzes_attempted'] }}</h2>
                        </div>
                        <i class="fas fa-question-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Average Score</h6>
                            <h2 class="mb-0">{{ number_format($stats['average_score'], 1) }}</h2>
                        </div>
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Quizzes Passed</h6>
                            <h2 class="mb-0">{{ $stats['quizzes_passed'] }}</h2>
                        </div>
                        <i class="fas fa-trophy fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Points Earned</h6>
                            <h2 class="mb-0">{{ $stats['total_points'] }}</h2>
                        </div>
                        <i class="fas fa-star fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Quizzes Section -->
    @if(isset($scheduledQuizzes) && $scheduledQuizzes->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt"></i> Upcoming Quizzes 
                            <span class="badge bg-light text-dark ms-2">{{ $scheduledQuizzes->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($scheduledQuizzes->take(2) as $quiz)
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 quiz-card border-warning">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0">{{ Str::limit($quiz->title, 40) }}</h5>
                                                <span class="quiz-badge badge-scheduled">
                                                    <i class="fas fa-clock"></i> Scheduled
                                                </span>
                                            </div>
                                            <p class="card-text text-muted small">
                                                {{ Str::limit($quiz->description ?? 'No description available.', 80) }}
                                            </p>
                                            <div class="mb-2">
                                                <span class="badge bg-secondary me-1">
                                                    <i class="far fa-clock"></i> {{ $quiz->duration_minutes }} min
                                                </span>
                                                <span class="badge bg-primary me-1">
                                                    <i class="fas fa-question"></i> {{ $quiz->questions_count }} Qs
                                                </span>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-star"></i> {{ $quiz->total_points }} pts
                                                </span>
                                            </div>
                                            
                                            <div class="schedule-info">
                                                <div class="schedule-time">
                                                    <i class="fas fa-calendar-alt"></i> 
                                                    <strong>Starts:</strong> {{ \Carbon\Carbon::parse($quiz->scheduled_at)->format('M d, Y h:i A') }}
                                                </div>
                                                @if($quiz->ends_at)
                                                    <div class="schedule-time mt-1">
                                                        <i class="fas fa-hourglass-end"></i> 
                                                        <strong>Ends:</strong> {{ \Carbon\Carbon::parse($quiz->ends_at)->format('M d, Y h:i A') }}
                                                    </div>
                                                @endif
                                                <div class="countdown-display mt-2">
                                                    <i class="fas fa-hourglass-half"></i> 
                                                    <span class="countdown-timer" data-start-time="{{ strtotime($quiz->scheduled_at) }}"></span>
                                                </div>
                                            </div>
                                            
                                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                                <i class="fas fa-clock"></i> Not Started Yet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Available Quizzes Section -->
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-play-circle"></i> Available Quizzes 
                        <span class="badge bg-light text-dark ms-2">{{ $availableQuizzes->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($availableQuizzes->count() > 0)
                        <div class="row">
                            @foreach($availableQuizzes->take(2) as $quiz)
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 quiz-card border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0">{{ Str::limit($quiz->title, 40) }}</h5>
                                                @if($quiz->category)
                                                    <span class="badge" style="background-color: {{ $quiz->category->color ?? '#6c757d' }}">
                                                        <i class="{{ $quiz->category->icon ?? 'fas fa-tag' }}"></i>
                                                        {{ Str::limit($quiz->category->name, 15) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="card-text text-muted small">
                                                {{ Str::limit($quiz->description ?? 'No description available.', 80) }}
                                            </p>
                                            <div class="mb-2">
                                                <span class="badge bg-secondary me-1">
                                                    <i class="far fa-clock"></i> {{ $quiz->duration_minutes }} min
                                                </span>
                                                <span class="badge bg-primary me-1">
                                                    <i class="fas fa-question"></i> {{ $quiz->questions_count }} Qs
                                                </span>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-star"></i> {{ $quiz->total_points }} pts
                                                </span>
                                            </div>
                                            @if($quiz->ends_at && $quiz->ends_at > now())
                                                <div class="alert alert-info p-1 mb-2 small text-center">
                                                    <i class="fas fa-hourglass-half"></i> Ends: {{ \Carbon\Carbon::parse($quiz->ends_at)->format('M d, Y h:i A') }}
                                                </div>
                                            @endif
                                            <a href="{{ route('user.quiz.lobby', $quiz) }}" class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-play"></i> Start Quiz
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5>No Quizzes Available</h5>
                            <p class="text-muted">There are no quizzes available at the moment. Please check back later!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tags"></i> My Categories 
                        <span class="badge bg-light text-dark ms-2">{{ $categories->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($categories->count() > 0)
                        @foreach($categories->take(2) as $category)
                            <div class="category-card d-flex align-items-center p-3 border rounded mb-3">
                                <div class="category-icon me-3">
                                    @if($category->icon)
                                        <i class="{{ $category->icon }}"></i>
                                    @else
                                        <i class="fas fa-tag"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $category->name }}</h6>
                                    <small class="text-muted">
                                        <i class="fas fa-question-circle"></i> {{ $category->quizzes_count }} quizzes
                                    </small>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ $category->quizzes_count }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No categories available.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Quizzes and Recent Attempts -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm equal-height-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-fire"></i> Featured Quizzes</h5>
                </div>
                <div class="card-body p-0">
                    @if(isset($featuredQuizzes) && $featuredQuizzes->count() > 0)
                        <div class="featured-list">
                            <div class="list-group list-group-flush">
                                @foreach($featuredQuizzes as $quiz)
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $quiz->title }}</h6>
                                            <small class="text-muted">{{ $quiz->questions_count }} questions</small>
                                        </div>
                                        <p class="mb-1 small text-muted">{{ Str::limit($quiz->description, 80) }}</p>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="badge bg-info">{{ $quiz->category->name ?? 'General' }}</span>
                                            <a href="{{ route('user.quiz.lobby', $quiz) }}" class="btn btn-sm btn-outline-success">
                                                Take Quiz <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-fire fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No featured quizzes available.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4 shadow-sm equal-height-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Attempts</h5>
                </div>
                <div class="card-body p-0">
                    @if($recentAttempts->count() > 0)
                        <div class="attempts-list">
                            <div class="list-group list-group-flush">
                                @foreach($recentAttempts as $attempt)
                                    <a href="{{ route('user.quiz.result', ['quiz' => $attempt->quiz_id, 'attempt' => $attempt->id]) }}" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $attempt->quiz->title }}</h6>
                                            <small>{{ $attempt->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div>
                                                <span class="badge bg-primary">Score: {{ $attempt->score }}</span>
                                                @if($attempt->result && $attempt->result->passed)
                                                    <span class="badge bg-success"><i class="fas fa-check"></i> Passed</span>
                                                @elseif($attempt->status == 'completed')
                                                    <span class="badge bg-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-warning">{{ ucfirst($attempt->status) }}</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ $attempt->correct_answers }}/{{ $attempt->total_questions }} correct
                                            </small>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No quiz attempts yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Countdown timers for scheduled quizzes
        function updateCountdowns() {
            const currentTime = Math.floor(Date.now() / 1000);
            
            $('.countdown-timer').each(function() {
                const startTime = parseInt($(this).data('start-time'));
                const timeRemaining = startTime - currentTime;
                
                if (timeRemaining <= 0) {
                    $(this).html('<span class="text-success"><i class="fas fa-play-circle"></i> Starting now...</span>');
                } else if (timeRemaining < 3600) {
                    const minutes = Math.floor(timeRemaining / 60);
                    const seconds = timeRemaining % 60;
                    $(this).html(`⏰ ${minutes}m ${seconds}s remaining`);
                } else {
                    const hours = Math.floor(timeRemaining / 3600);
                    const minutes = Math.floor((timeRemaining % 3600) / 60);
                    $(this).html(`📅 ${hours}h ${minutes}m remaining`);
                }
            });
        }
        
        // Update countdowns every second
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
        
        // Auto-refresh page every 30 seconds to update quizzes
        setInterval(function() {
            location.reload();
        }, 30000);
    });
</script>
@endpush
@endsection