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
    .progress-bar {
        transition: width 0.5s ease;
    }
    .category-badge {
        transition: all 0.2s ease;
    }
    .category-badge:hover {
        transform: scale(1.05);
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-smile-wink"></i> Welcome back, <strong>{{ Auth::user()->name }}</strong>! Ready to test your knowledge?
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
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

<!-- Available Quizzes Section -->
<div class="row">
    <div class="col-md-12">
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
                        @foreach($availableQuizzes as $quiz)
                            <div class="col-md-6 col-lg-4 mb-4">
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
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-trophy"></i> Passing: {{ $quiz->passing_score }}%
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-redo"></i> Attempts left: 
                                                @php
                                                    $attemptsCount = \App\Models\QuizAttempt::where('user_id', Auth::id())
                                                        ->where('quiz_id', $quiz->id)
                                                        ->count();
                                                    $remaining = $quiz->max_attempts - $attemptsCount;
                                                @endphp
                                                {{ $remaining }}
                                            </small>
                                        </div>
                                        @if($quiz->ends_at)
                                            <div class="alert alert-warning p-1 mb-2 small text-center">
                                                <i class="fas fa-hourglass-half"></i> Ends: {{ $quiz->ends_at->format('M d, Y') }}
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
</div>

<div class="row">
    <!-- Featured Quizzes -->
    @if(isset($featuredQuizzes) && $featuredQuizzes->count() > 0)
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-fire"></i> Featured Quizzes</h5>
                </div>
                <div class="card-body p-0">
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
            </div>
        </div>
    @endif

    <!-- Recent Attempts -->
    <div class="col-md-6">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Attempts</h5>
            </div>
            <div class="card-body p-0">
                @if($recentAttempts->count() > 0)
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
                                @if($attempt->result && $attempt->result->percentage)
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar {{ $attempt->result->passed ? 'bg-success' : 'bg-danger' }}" 
                                             style="width: {{ $attempt->result->percentage }}%"></div>
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    <div class="text-center p-3">
                        <a href="{{ route('user.results') }}" class="btn btn-outline-primary btn-sm">
                            View All Results <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No quiz attempts yet.</p>
                        @if($availableQuizzes->count() > 0)
                            <p class="small text-muted">Start your first quiz from the available quizzes above!</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Categories -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-tags"></i> Categories</h5>
            </div>
            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="row">
                        @foreach($categories as $category)
                            <div class="col-md-6 mb-2">
                                <div class="d-flex justify-content-between align-items-center p-2 border rounded category-badge">
                                    <span>
                                        @if($category->icon)
                                            <i class="{{ $category->icon }}" style="color: {{ $category->color }}"></i>
                                        @else
                                            <i class="fas fa-tag" style="color: {{ $category->color }}"></i>
                                        @endif
                                        {{ $category->name }}
                                    </span>
                                    <span class="badge bg-primary rounded-pill">{{ $category->quizzes_count }} quizzes</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center mb-0">No categories available.</p>
                @endif
            </div>
        </div>
        
        <!-- Performance Chart (Optional) -->
        @if($performanceData->count() > 0)
        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Your Performance (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="performanceChart" height="200"></canvas>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if($performanceData->count() > 0)
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceData = @json($performanceData);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: performanceData.map(item => item.date),
            datasets: [
                {
                    label: 'Average Score',
                    data: performanceData.map(item => item.avg_score),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Attempts',
                    data: performanceData.map(item => item.attempts),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1',
                    type: 'bar'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Average Score'
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Attempts'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    @endif
    
    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
@endpush
@endsection