@extends('layouts.admin')

@section('title', 'Quiz Performance Report')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Quiz Performance Report</h5>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="quiz_id" class="form-label">Quiz</label>
                        <select name="quiz_id" id="quiz_id" class="form-select">
                            <option value="">All Quizzes</option>
                            @foreach($quizzes as $quiz)
                                <option value="{{ $quiz->id }}" {{ request('quiz_id') == $quiz->id ? 'selected' : '' }}>
                                    {{ $quiz->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                        <a href="{{ route('admin.reports.quiz-performance') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title">Total Attempts</h6>
                                <h3>{{ $summary['total_attempts'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">Completed Attempts</h6>
                                <h3>{{ $summary['completed_attempts'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title">Average Score</h6>
                                <h3>{{ number_format($summary['average_score'], 1) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="card-title">Pass Rate</h6>
                                <h3>{{ number_format($summary['pass_rate'], 1) }}%</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Stats Chart -->
                @if($dailyStats->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Daily Performance Trend</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyChart" height="300"></canvas>
                        </div>
                    </div>
                @endif

                <!-- Top Performers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-trophy"></i> Top Performers</h6>
                    </div>
                    <div class="card-body">
                        @if($topPerformers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover" id="topPerformersTable">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>User</th>
                                            <th>Quiz</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topPerformers as $index => $performer)
                                            <tr>
                                                <td>
                                                    @if($index == 0)
                                                        <span class="badge bg-warning">🥇 1st</span>
                                                    @elseif($index == 1)
                                                        <span class="badge bg-secondary">🥈 2nd</span>
                                                    @elseif($index == 2)
                                                        <span class="badge bg-danger">🥉 3rd</span>
                                                    @else
                                                        <span class="badge bg-info">{{ $index + 1 }}th</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 32px; height: 32px;">
                                                            {{ strtoupper(substr($performer->user->name, 0, 1)) }}
                                                        </div>
                                                        <strong>{{ $performer->user->name }}</strong>
                                                    </div>
                                                </td>
                                                <td>{{ $performer->quiz->title }}</td>
                                                <td>
                                                    <span class="badge bg-success">{{ $performer->score }} pts</span>
                                                </td>
                                                <td>{{ $performer->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No attempts found.</p>
                        @endif
                    </div>
                </div>

                <!-- All Attempts Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list"></i> All Attempts</h6>
                    </div>
                    <div class="card-body">
                        @if($attempts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover" id="attemptsTable">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Quiz</th>
                                            <th>Score</th>
                                            <th>Correct</th>
                                            <th>Incorrect</th>
                                            <th>Percentage</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($attempts as $attempt)
                                            <tr>
                                                <td>{{ $attempt->user->name }}</td>
                                                <td>{{ $attempt->quiz->title }}</td>
                                                <td><strong>{{ $attempt->score }}</strong></td>
                                                <td><span class="text-success">{{ $attempt->correct_answers }}</span></td>
                                                <td><span class="text-danger">{{ $attempt->incorrect_answers }}</span></td>
                                                <td>
                                                    @php
                                                        $percentage = $attempt->quiz->total_points > 0 
                                                            ? ($attempt->score / $attempt->quiz->total_points) * 100 
                                                            : 0;
                                                    @endphp
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar {{ $percentage >= $attempt->quiz->passing_score ? 'bg-success' : 'bg-warning' }}" 
                                                             style="width: {{ $percentage }}%">
                                                            {{ round($percentage, 1) }}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($attempt->status == 'completed')
                                                        @if($attempt->result && $attempt->result->passed)
                                                            <span class="badge bg-success">Passed</span>
                                                        @else
                                                            <span class="badge bg-danger">Failed</span>
                                                        @endif
                                                    @elseif($attempt->status == 'in_progress')
                                                        <span class="badge bg-warning">In Progress</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($attempt->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $attempt->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No attempts found.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables for attempts table if it exists
        if ($('#attemptsTable').length && $.fn.DataTable.isDataTable('#attemptsTable')) {
            $('#attemptsTable').DataTable().destroy();
        }
        
        if ($('#attemptsTable').length) {
            $('#attemptsTable').DataTable({
                pageLength: 10,
                responsive: true,
                ordering: true,
                searching: true,
                paging: true,
                info: true
            });
        }
        
        // Initialize DataTables for top performers table
        if ($('#topPerformersTable').length && $.fn.DataTable.isDataTable('#topPerformersTable')) {
            $('#topPerformersTable').DataTable().destroy();
        }
        
        if ($('#topPerformersTable').length) {
            $('#topPerformersTable').DataTable({
                paging: false,
                searching: false,
                info: false
            });
        }
    });
    
    @if($dailyStats->count() > 0)
    const ctx = document.getElementById('dailyChart').getContext('2d');
    const dailyStats = @json($dailyStats);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyStats.map(item => item.date),
            datasets: [
                {
                    label: 'Number of Attempts',
                    data: dailyStats.map(item => item.attempts),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Average Score',
                    data: dailyStats.map(item => item.avg_score),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: true,
                    yAxisID: 'y1'
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
                        text: 'Number of Attempts'
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Average Score'
                    }
                }
            }
        }
    });
    @endif
</script>
@endpush
@endsection