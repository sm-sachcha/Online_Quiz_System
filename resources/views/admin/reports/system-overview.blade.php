@extends('layouts.admin')

@section('title', 'System Overview')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4"><i class="fas fa-chart-pie"></i> System Overview</h2>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mb-0">{{ $totalUsers }}</h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Admins</h6>
                        <h2 class="mb-0">{{ $totalAdmins }}</h2>
                    </div>
                    <i class="fas fa-user-tie fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Quizzes</h6>
                        <h2 class="mb-0">{{ $totalQuizzes }}</h2>
                    </div>
                    <i class="fas fa-question-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Attempts</h6>
                        <h2 class="mb-0">{{ $totalAttempts }}</h2>
                    </div>
                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Published Quizzes</h6>
                <h3>{{ $publishedQuizzes }}</h3>
                <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: {{ $totalQuizzes > 0 ? ($publishedQuizzes / $totalQuizzes) * 100 : 0 }}%"></div>
                </div>
                <small class="text-muted">{{ $totalQuizzes > 0 ? round(($publishedQuizzes / $totalQuizzes) * 100, 1) : 0 }}% of total</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Completed Attempts</h6>
                <h3>{{ $completedAttempts }}</h3>
                <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar bg-info" style="width: {{ $totalAttempts > 0 ? ($completedAttempts / $totalAttempts) * 100 : 0 }}%"></div>
                </div>
                <small class="text-muted">{{ $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 1) : 0 }}% completion rate</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Active Users (7 days)</h6>
                <h3>{{ $activeUsers }}</h3>
                <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar bg-warning" style="width: {{ $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0 }}%"></div>
                </div>
                <small class="text-muted">{{ $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0 }}% active</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Category Distribution -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Quiz Distribution by Category</h5>
            </div>
            <div class="card-body">
                @if($categoryDistribution->count() > 0)
                    <canvas id="categoryChart" height="300"></canvas>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No category data available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Popular Quizzes -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-fire"></i> Most Popular Quizzes</h5>
            </div>
            <div class="card-body p-0">
                @if($popularQuizzes->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($popularQuizzes as $quiz)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $quiz->title }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-tag"></i> {{ $quiz->category->name ?? 'Uncategorized' }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary rounded-pill">{{ $quiz->attempts_count }} attempts</span>
                                        <br>
                                        <small class="text-muted">{{ $quiz->questions_count }} questions</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-fire fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No quizzes found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- New Users Growth -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> New Users (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                @if(isset($userGrowth) && $userGrowth->count() > 0)
                    <canvas id="userGrowthChart" height="300"></canvas>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No user growth data available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- System Activity Trend -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> System Activity (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                @if($activityByDay->count() > 0)
                    <canvas id="activityTrendChart" height="300"></canvas>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No activity data available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Registrations -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Recent Registrations</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>{{ $recentRegistrations }}</strong> new users registered in the last 30 days.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Category Distribution Chart
        @if($categoryDistribution->count() > 0)
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = @json($categoryDistribution);
        
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: categoryData.map(item => item.name),
                datasets: [{
                    data: categoryData.map(item => item.total),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                        '#FF9F40', '#FF6384', '#C9CBCF', '#FF6384', '#36A2EB'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} quizzes (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        @endif
        
        // User Growth Chart
        @if(isset($userGrowth) && $userGrowth->count() > 0)
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthData = @json($userGrowth);
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: userGrowthData.map(item => item.date),
                datasets: [{
                    label: 'New Users',
                    data: userGrowthData.map(item => item.new_users),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(75, 192, 192)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `New Users: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Number of New Users'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
        @endif
        
        // Activity Trend Chart
        @if($activityByDay->count() > 0)
        const activityTrendCtx = document.getElementById('activityTrendChart').getContext('2d');
        const activityTrendData = @json($activityByDay);
        
        new Chart(activityTrendCtx, {
            type: 'bar',
            data: {
                labels: activityTrendData.map(item => item.date),
                datasets: [{
                    label: 'System Activities',
                    data: activityTrendData.map(item => item.count),
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgb(255, 159, 64)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Activities: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Number of Activities'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
        @endif
    });
</script>
@endpush
@endsection