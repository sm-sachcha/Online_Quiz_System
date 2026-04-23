@extends('layouts.admin')

@section('title', 'Quiz Performance Report')

@section('content')
<style>
    .guest-badge {
        background-color: #17a2b8;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }
    .avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    .avatar-guest {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
</style>

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
                <!-- <div class="card mb-4">
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
                                         </thead>
                                    <tbody>
                                        @foreach($topPerformers as $index => $performer)
                                            @php
                                                $isGuest = is_null($performer->user_id);
                                                $userName = $isGuest ? ($performer->participant->guest_name ?? 'Quiz_Participant') : ($performer->user->name ?? 'Unknown User');
                                                $avatarLetter = $userName ? strtoupper(substr($userName, 0, 1)) : '?';
                                                $avatarClass = $isGuest ? 'avatar-guest' : '';
                                            @endphp
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
                                                        <div class="avatar-sm me-2 {{ $avatarClass }}">
                                                            {{ $avatarLetter }}
                                                        </div>
                                                        <div>
                                                            <strong>{{ $userName }}</strong>
                                                            @if($isGuest)
                                                                <span class="guest-badge">Quiz_Participant</span>
                                                            @endif
                                                        </div>
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
                </div> -->

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
                                         </thead>
                                    <tbody>
                                        @foreach($attempts as $attempt)
                                            @php
                                                $isGuest = is_null($attempt->user_id);
                                                $userName = $isGuest ? ($attempt->participant->guest_name ?? '') : ($attempt->user->name ?? 'Unknown User');
                                                $userEmail = $isGuest ? '' : ($attempt->user->email ?? 'N/A');
                                                $avatarLetter = $userName ? strtoupper(substr($userName, 0, 1)) : '?';
                                                $avatarClass = $isGuest ? 'avatar-guest' : '';
                                                $percentage = $attempt->quiz->total_points > 0 
                                                    ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1) 
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2 {{ $avatarClass }}">
                                                            {{ $avatarLetter }}
                                                        </div>
                                                        <div>
                                                            <strong>{{ $userName }}</strong>
                                                            @if($isGuest)
                                                                <span class="guest-badge">Quiz_Participant</span>
                                                            @endif
                                                            <br>
                                                            <small class="text-muted">{{ $userEmail }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong>{{ $attempt->quiz->title }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $attempt->quiz->category->name ?? 'Uncategorized' }}</small>
                                                </td>
                                                <td>
                                                    <strong>{{ $attempt->score }}</strong>
                                                    <br>
                                                    <small>/ {{ $attempt->quiz->total_points }} pts</small>
                                                </td>
                                                <td>
                                                    <span class="text-success">{{ $attempt->correct_answers }}</span>
                                                    <br>
                                                    <small>correct</small>
                                                </td>
                                                <td>
                                                    <span class="text-danger">{{ $attempt->incorrect_answers }}</span>
                                                    <br>
                                                    <small>incorrect</small>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 25px; width: 120px;">
                                                        <div class="progress-bar {{ $percentage >= $attempt->quiz->passing_score ? 'bg-success' : 'bg-warning' }}" 
                                                             style="width: {{ $percentage }}%">
                                                            {{ $percentage }}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($attempt->status == 'completed')
                                                        @if($attempt->result && $attempt->result->passed)
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle"></i> Passed
                                                            </span>
                                                        @else
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle"></i> Failed
                                                            </span>
                                                        @endif
                                                    @elseif($attempt->status == 'in_progress')
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-spinner"></i> In Progress
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            {{ ucfirst($attempt->status) }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <i class="far fa-calendar-alt text-muted"></i>
                                                    {{ $attempt->created_at->format('M d, Y') }}
                                                    <br>
                                                    <small class="text-muted">{{ $attempt->created_at->format('h:i A') }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @if(method_exists($attempts, 'links'))
                                <div class="mt-3">
                                    {{ $attempts->withQueryString()->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                                <h5>No Attempts Found</h5>
                                <p class="text-muted">No quiz attempts match your filter criteria.</p>
                                <a href="{{ route('admin.reports.quiz-performance') }}" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Reset Filters
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables for attempts table if it exists
        if ($('#attemptsTable').length) {
            if ($.fn.DataTable.isDataTable('#attemptsTable')) {
                $('#attemptsTable').DataTable().destroy();
            }
            
            $('#attemptsTable').DataTable({
                pageLength: 10,
                responsive: true,
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    emptyTable: "No attempts found"
                },
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });
        }
        
        // Initialize DataTables for top performers table
        if ($('#topPerformersTable').length) {
            if ($.fn.DataTable.isDataTable('#topPerformersTable')) {
                $('#topPerformersTable').DataTable().destroy();
            }
            
            $('#topPerformersTable').DataTable({
                paging: false,
                searching: false,
                info: false,
                ordering: true
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
                    },
                    ticks: {
                        stepSize: 1
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