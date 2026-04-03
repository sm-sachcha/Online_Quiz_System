@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
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

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Admin Dashboard</h2>
        <p class="text-muted">Welcome back, <strong>{{ Auth::user()->name }}</strong>!</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mb-0">{{ $stats['total_users'] }}</h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Quizzes</h6>
                        <h2 class="mb-0">{{ $stats['total_quizzes'] }}</h2>
                    </div>
                    <i class="fas fa-question-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-info stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Categories</h6>
                        <h2 class="mb-0">{{ $stats['total_categories'] }}</h2>
                    </div>
                    <i class="fas fa-tags fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-warning stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Attempts</h6>
                        <h2 class="mb-0">{{ $stats['total_attempts'] }}</h2>
                    </div>
                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Second Row Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Published Quizzes</h6>
                <h3>{{ $stats['published_quizzes'] }}</h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Active Quizzes</h6>
                <h3>{{ $stats['active_quizzes'] }}</h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Average Score</h6>
                <h3>{{ number_format($stats['average_score'], 1) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Quizzes -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Quizzes</h5>
            </div>
            <div class="card-body p-0">
                @if($recentQuizzes->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentQuizzes as $quiz)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $quiz->title }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> {{ $quiz->creator->name ?? 'Unknown' }} |
                                            @if($quiz->category)
                                                <i class="fas fa-tag"></i> {{ $quiz->category->name }}
                                            @else
                                                <i class="fas fa-globe"></i> Public Quiz
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge {{ $quiz->is_published ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $quiz->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No quizzes created yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users"></i> Recent Users</h5>
            </div>
            <div class="card-body p-0">
                @if($recentUsers->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentUsers as $user)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $user->name }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i> {{ $user->email }} |
                                            <i class="fas fa-calendar"></i> {{ $user->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No users registered yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Quizzes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-fire"></i> Most Popular Quizzes</h5>
            </div>
            <div class="card-body">
                @if($topQuizzes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Attempts</th>
                                 </thead>
                            <tbody>
                                @foreach($topQuizzes as $quiz)
                                    <tr>
                                        <td>{{ $quiz->title }}</td>
                                        <td><span class="badge bg-primary">{{ $quiz->attempts_count }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                         </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No attempts yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Activity Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> User Activity (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('activityChart').getContext('2d');
    const activityData = @json($activityData);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: activityData.map(item => item.date),
            datasets: [{
                label: 'User Activities',
                data: activityData.map(item => item.count),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
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
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection