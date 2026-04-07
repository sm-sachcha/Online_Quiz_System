@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
<style>
    /* Stats Cards - 3 Cards Auto Size */
    .row.mb-4 {
        margin: 0 -0.5rem;
    }
    .row.mb-4 > [class*="col-"] {
        padding: 0 0.5rem;
    }
    .stat-card {
        transition: transform 0.3s ease;
        cursor: pointer;
        border-radius: 12px;
        overflow: hidden;
        height: 100%;
        min-height: 120px;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-card .card-body {
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
    }
    .stat-card .card-body > div {
        flex: 1;
    }
    .stat-card h2 {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 0;
        line-height: 1.2;
    }
    .stat-card h6 {
        font-size: 0.8rem;
        opacity: 0.9;
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    .stat-card i {
        opacity: 0.5;
        font-size: 2rem;
        margin-left: 0.5rem;
    }
    
    /* Quiz Cards */
    .quiz-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .quiz-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .quiz-card .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1rem;
    }
    .quiz-card .card-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .quiz-card .card-text {
        font-size: 0.8rem;
        margin-bottom: 0.75rem;
        flex: 1;
    }
    .quiz-card .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    .quiz-card .btn {
        margin-top: auto;
        font-size: 0.85rem;
        padding: 0.5rem;
    }
    
    /* Fixed Height Cards */
    .fixed-height-card {
        height: 550px;
        display: flex;
        flex-direction: column;
        border-radius: 12px;
        overflow: hidden;
    }
    .fixed-height-card .card-header {
        flex-shrink: 0;
        padding: 0.75rem 1rem;
    }
    .fixed-height-card .card-header h5 {
        font-size: 1rem;
        margin: 0;
    }
    .fixed-height-card .card-body {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }
    
    /* Equal Height Cards */
    .equal-height-card {
        height: 450px;
        display: flex;
        flex-direction: column;
        border-radius: 12px;
        overflow: hidden;
    }
    .equal-height-card .card-header {
        flex-shrink: 0;
        padding: 0.75rem 1rem;
    }
    .equal-height-card .card-header h5 {
        font-size: 1rem;
        margin: 0;
    }
    .equal-height-card .card-body {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }
    
    /* Category Cards */
    .category-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border-radius: 10px;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        background: #fff;
    }
    .category-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .category-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .category-card h6 {
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
    }
    .category-card small {
        font-size: 0.7rem;
    }
    .category-card .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.6rem;
        flex-shrink: 0;
    }
    
    /* List Items */
    .list-group-item {
        padding: 0.75rem 1rem;
    }
    .list-group-item h6 {
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
    }
    .list-group-item p {
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }
    .list-group-item small {
        font-size: 0.7rem;
    }
    .list-group-item .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
    }
    
    /* Scheduled Cards */
    .scheduled-card {
        margin-bottom: 1.5rem;
        border-radius: 12px;
        overflow: hidden;
    }
    .scheduled-card .card-header {
        padding: 0.75rem 1rem;
    }
    .scheduled-card .card-header h5 {
        font-size: 1rem;
        margin: 0;
    }
    .scheduled-card .card-body {
        max-height: 500px;
        overflow-y: auto;
        padding: 1rem;
    }
    .schedule-info {
        background-color: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 0.5rem;
        margin-bottom: 0.75rem;
        font-size: 0.75rem;
    }
    .schedule-info i {
        margin-right: 0.25rem;
    }
    .countdown-timer {
        font-family: monospace;
        font-weight: bold;
        font-size: 0.8rem;
        color: #856404;
    }
    .quiz-badge {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
    }
    .badge-scheduled {
        background-color: #ffc107;
        color: #856404;
    }
    .btn-disabled {
        background-color: #6c757d;
        border-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.65;
        font-size: 0.8rem;
        padding: 0.4rem;
    }
    
    /* See More Button */
    .see-more-btn {
        background-color: #6c757d;
        color: white;
        transition: all 0.3s ease;
        border: none;
        padding: 0.4rem 1.2rem;
        border-radius: 20px;
        font-size: 0.8rem;
        margin-top: 0.5rem;
    }
    .see-more-btn:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }
    
    /* Hide/Show */
    .more-quizzes, .more-categories, .more-scheduled {
        display: none;
    }
    .more-quizzes.show, .more-categories.show, .more-scheduled.show {
        display: block;
    }
    
    /* Scrollbars */
    .card-body::-webkit-scrollbar {
        width: 5px;
    }
    .card-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 5px;
    }
    .card-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 5px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .fixed-height-card, .equal-height-card {
            height: auto;
            min-height: 400px;
        }
        .stat-card h2 {
            font-size: 1.3rem;
        }
        .stat-card h6 {
            font-size: 0.7rem;
        }
        .stat-card i {
            font-size: 1.5rem;
        }
        .stat-card .card-body {
            padding: 0.75rem;
        }
    }
</style>

<div id="dashboardContent">
    <div class="row">
        <div class="col-md-12">
            @if(session('show_welcome') && session('welcome_message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-smile-wink"></i> {{ session('welcome_message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @php
                    session()->forget('show_welcome');
                    session()->forget('welcome_message');
                @endphp
            @endif
        </div>
    </div>

    <!-- Stats Cards - 3 Cards -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card text-white bg-primary stat-card">
                <div class="card-body">
                    <div>
                        <h6 class="card-title">Total Quizzes Taken</h6>
                        <h2 class="mb-0">{{ $stats['total_quizzes_attempted'] }}</h2>
                    </div>
                    <i class="fas fa-question-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card text-white bg-success stat-card">
                <div class="card-body">
                    <div>
                        <h6 class="card-title">Average Score</h6>
                        <h2 class="mb-0">{{ number_format($stats['average_score'], 1) }}</h2>
                    </div>
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card text-white bg-info stat-card">
                <div class="card-body">
                    <div>
                        <h6 class="card-title">Quizzes Passed</h6>
                        <h2 class="mb-0">{{ $stats['quizzes_passed'] }}</h2>
                    </div>
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Quizzes Section -->
    @if(isset($scheduledQuizzes) && $scheduledQuizzes->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 shadow-sm scheduled-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt"></i> Upcoming Quizzes 
                            <span class="badge bg-light text-dark ms-2">{{ $scheduledQuizzes->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $initialScheduled = $scheduledQuizzes->take(2);
                            $remainingScheduled = $scheduledQuizzes->slice(2);
                        @endphp
                        
                        <div class="row" id="initialScheduled">
                            @foreach($initialScheduled as $quiz)
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 quiz-card border-warning">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0">{{ Str::limit($quiz->title, 35) }}</h5>
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
                                                <div>
                                                    <i class="fas fa-calendar-alt"></i> 
                                                    <strong>Starts:</strong> {{ \Carbon\Carbon::parse($quiz->scheduled_at)->format('M d, Y h:i A') }}
                                                </div>
                                                @if($quiz->ends_at)
                                                    <div class="mt-1">
                                                        <i class="fas fa-hourglass-end"></i> 
                                                        <strong>Ends:</strong> {{ \Carbon\Carbon::parse($quiz->ends_at)->format('M d, Y h:i A') }}
                                                    </div>
                                                @endif
                                                <div class="mt-2">
                                                    <span class="countdown-timer" data-start-time="{{ strtotime($quiz->scheduled_at) }}"></span>
                                                </div>
                                            </div>
                                            
                                            <button class="btn btn-secondary btn-sm w-100 btn-disabled" disabled>
                                                <i class="fas fa-clock"></i> Not Started Yet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($remainingScheduled->count() > 0)
                            <div id="moreScheduled" class="more-scheduled">
                                <div class="row">
                                    @foreach($remainingScheduled as $quiz)
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100 quiz-card border-warning">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="card-title mb-0">{{ Str::limit($quiz->title, 35) }}</h5>
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
                                                        <div>
                                                            <i class="fas fa-calendar-alt"></i> 
                                                            <strong>Starts:</strong> {{ \Carbon\Carbon::parse($quiz->scheduled_at)->format('M d, Y h:i A') }}
                                                        </div>
                                                        @if($quiz->ends_at)
                                                            <div class="mt-1">
                                                                <i class="fas fa-hourglass-end"></i> 
                                                                <strong>Ends:</strong> {{ \Carbon\Carbon::parse($quiz->ends_at)->format('M d, Y h:i A') }}
                                                            </div>
                                                        @endif
                                                        <div class="mt-2">
                                                            <span class="countdown-timer" data-start-time="{{ strtotime($quiz->scheduled_at) }}"></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <button class="btn btn-secondary btn-sm w-100 btn-disabled" disabled>
                                                        <i class="fas fa-clock"></i> Not Started Yet
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <button class="see-more-btn" id="toggleScheduledBtn">
                                    <i class="fas fa-chevron-down"></i> See More ({{ $remainingScheduled->count() }} more)
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Available Quizzes and Categories Section -->
    <div class="row">
        <!-- Available Quizzes Section -->
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm fixed-height-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-play-circle"></i> Available Quizzes 
                        <span class="badge bg-light text-dark ms-2">{{ $availableQuizzes->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($availableQuizzes->count() > 0)
                        @php
                            $initialQuizzes = $availableQuizzes->take(2);
                            $remainingQuizzes = $availableQuizzes->slice(2);
                        @endphp
                        
                        <div id="initialQuizzes">
                            <div class="row">
                                @foreach($initialQuizzes as $quiz)
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 quiz-card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title mb-0">{{ Str::limit($quiz->title, 35) }}</h5>
                                                    @if($quiz->category)
                                                        <span class="badge" style="background-color: {{ $quiz->category->color ?? '#6c757d' }}">
                                                            <i class="{{ $quiz->category->icon ?? 'fas fa-tag' }}"></i>
                                                            {{ Str::limit($quiz->category->name, 12) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="card-text text-muted small">
                                                    {{ Str::limit($quiz->description ?? 'No description available.', 70) }}
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
                                                        <i class="fas fa-hourglass-half"></i> Ends: {{ \Carbon\Carbon::parse($quiz->ends_at)->format('M d, Y') }}
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
                        </div>
                        
                        @if($remainingQuizzes->count() > 0)
                            <div id="moreQuizzes" class="more-quizzes">
                                <div class="row">
                                    @foreach($remainingQuizzes as $quiz)
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100 quiz-card border-0 shadow-sm">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="card-title mb-0">{{ Str::limit($quiz->title, 35) }}</h5>
                                                        @if($quiz->category)
                                                            <span class="badge" style="background-color: {{ $quiz->category->color ?? '#6c757d' }}">
                                                                <i class="{{ $quiz->category->icon ?? 'fas fa-tag' }}"></i>
                                                                {{ Str::limit($quiz->category->name, 12) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="card-text text-muted small">
                                                        {{ Str::limit($quiz->description ?? 'No description available.', 70) }}
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
                                                            <i class="fas fa-hourglass-half"></i> Ends: {{ \Carbon\Carbon::parse($quiz->ends_at)->format('M d, Y') }}
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
                            </div>
                            
                            <div class="text-center mt-3">
                                <button class="see-more-btn" id="toggleQuizzesBtn">
                                    <i class="fas fa-chevron-down"></i> See More ({{ $remainingQuizzes->count() }} more)
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5>No Quizzes Available</h5>
                            <p class="text-muted">There are no quizzes available at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm fixed-height-card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tags"></i> My Categories 
                        <span class="badge bg-light text-dark ms-2">{{ $categories->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($categories->count() > 0)
                        @php
                            $initialCategories = $categories->take(2);
                            $remainingCategories = $categories->slice(2);
                        @endphp
                        
                        <div id="initialCategories">
                            @foreach($initialCategories as $category)
                                <div class="category-card d-flex align-items-center border rounded">
                                    <div class="category-icon me-3">
                                        @if($category->icon)
                                            <i class="{{ $category->icon }}"></i>
                                        @else
                                            <i class="fas fa-tag"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $category->name }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-question-circle"></i> {{ $category->quizzes_count }} quizzes
                                        </small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ $category->quizzes_count }}</span>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($remainingCategories->count() > 0)
                            <div id="moreCategories" class="more-categories">
                                @foreach($remainingCategories as $category)
                                    <div class="category-card d-flex align-items-center border rounded">
                                        <div class="category-icon me-3">
                                            @if($category->icon)
                                                <i class="{{ $category->icon }}"></i>
                                            @else
                                                <i class="fas fa-tag"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $category->name }}</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-question-circle"></i> {{ $category->quizzes_count }} quizzes
                                            </small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">{{ $category->quizzes_count }}</span>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="text-center mt-3">
                                <button class="see-more-btn" id="toggleCategoriesBtn">
                                    <i class="fas fa-chevron-down"></i> See More ({{ $remainingCategories->count() }} more)
                                </button>
                            </div>
                        @endif
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
                        <div class="list-group list-group-flush">
                            @foreach($featuredQuizzes as $quiz)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ Str::limit($quiz->title, 30) }}</h6>
                                        <small class="text-muted">{{ $quiz->questions_count }} Qs</small>
                                    </div>
                                    <p class="mb-1 small text-muted">{{ Str::limit($quiz->description, 60) }}</p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge bg-info">{{ $quiz->category->name ?? 'General' }}</span>
                                        <a href="{{ route('user.quiz.lobby', $quiz) }}" class="btn btn-sm btn-outline-success">
                                            Take Quiz <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
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
                        <div class="list-group list-group-flush">
                            @foreach($recentAttempts as $attempt)
                                <a href="{{ route('user.quiz.result', ['quiz' => $attempt->quiz_id, 'attempt' => $attempt->id]) }}" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ Str::limit($attempt->quiz->title, 30) }}</h6>
                                        <small>{{ $attempt->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div>
                                            <span class="badge bg-primary">Score: {{ $attempt->score }}</span>
                                            @if($attempt->result && $attempt->result->passed)
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Passed</span>
                                            @elseif($attempt->status == 'completed')
                                                <span class="badge bg-secondary">Completed</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">
                                            {{ $attempt->correct_answers }}/{{ $attempt->total_questions }} correct
                                        </small>
                                    </div>
                                </a>
                            @endforeach
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
        // Toggle more quizzes
        $('#toggleQuizzesBtn').on('click', function(e) {
            e.preventDefault();
            const moreQuizzes = $('#moreQuizzes');
            const btn = $(this);
            
            if (moreQuizzes.hasClass('show')) {
                moreQuizzes.removeClass('show');
                btn.html('<i class="fas fa-chevron-down"></i> See More (' + moreQuizzes.find('.col-md-6').length + ' more)');
            } else {
                moreQuizzes.addClass('show');
                btn.html('<i class="fas fa-chevron-up"></i> Show Less');
            }
        });
        
        // Toggle more categories
        $('#toggleCategoriesBtn').on('click', function(e) {
            e.preventDefault();
            const moreCategories = $('#moreCategories');
            const btn = $(this);
            
            if (moreCategories.hasClass('show')) {
                moreCategories.removeClass('show');
                btn.html('<i class="fas fa-chevron-down"></i> See More (' + moreCategories.find('.category-card').length + ' more)');
            } else {
                moreCategories.addClass('show');
                btn.html('<i class="fas fa-chevron-up"></i> Show Less');
            }
        });
        
        // Toggle more scheduled quizzes
        $('#toggleScheduledBtn').on('click', function(e) {
            e.preventDefault();
            const moreScheduled = $('#moreScheduled');
            const btn = $(this);
            
            if (moreScheduled.hasClass('show')) {
                moreScheduled.removeClass('show');
                btn.html('<i class="fas fa-chevron-down"></i> See More (' + moreScheduled.find('.col-md-6').length + ' more)');
            } else {
                moreScheduled.addClass('show');
                btn.html('<i class="fas fa-chevron-up"></i> Show Less');
            }
        });
        
        // Countdown timers
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
                    $(this).html(`<i class="fas fa-hourglass-half"></i> ${minutes}m ${seconds}s remaining`);
                } else {
                    const hours = Math.floor(timeRemaining / 3600);
                    const minutes = Math.floor((timeRemaining % 3600) / 60);
                    $(this).html(`<i class="fas fa-calendar-day"></i> ${hours}h ${minutes}m remaining`);
                }
            });
        }
        
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    });
</script>
@endpush
@endsection