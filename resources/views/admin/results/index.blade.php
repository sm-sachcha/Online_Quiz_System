@extends('layouts.admin')

@section('title', 'Quiz Results')

@section('content')
<style>
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
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-passed {
        background-color: #28a745;
        color: white;
    }
    .status-failed {
        background-color: #dc3545;
        color: white;
    }
    .rank-1 {
        background-color: #ffd700;
        color: #856404;
    }
    .rank-2 {
        background-color: #c0c0c0;
        color: #495057;
    }
    .rank-3 {
        background-color: #cd7f32;
        color: white;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Quiz Results</h5>
                <button type="button" class="btn btn-success btn-sm" id="exportBtn">
                    <i class="fas fa-download"></i> Export to CSV
                </button>
            </div>
            <div class="card-body">
                <!-- Creator Info Alert -->
                @if(!Auth::user()->isMasterAdmin())
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> You are viewing results only for quizzes you created.
                    </div>
                @endif

                <!-- Filter Form -->
                <form method="GET" class="mb-4" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="quiz_id" class="form-label">Select Quiz</label>
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
                            <label for="user_id" class="form-label">User</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="passed" class="form-label">Status</label>
                            <select name="passed" id="passed" class="form-select">
                                <option value="">All</option>
                                <option value="passed" {{ request('passed') == 'passed' ? 'selected' : '' }}>Passed</option>
                                <option value="failed" {{ request('passed') == 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                            <a href="{{ route('admin.results.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Selected Quiz Information -->
                @if($selectedQuiz)
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Quiz:</strong> {{ $selectedQuiz->title }} | 
                        <strong>Total Points:</strong> {{ $selectedQuiz->total_points }} | 
                        <strong>Passing Score:</strong> {{ $selectedQuiz->passing_score }}%
                    </div>
                @endif

                <!-- Results Table -->
                @if($attempts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover" id="resultsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Quiz</th>
                                    <th>Obtained Marks</th>
                                    <th>Total Marks</th>
                                    <th>Percentage</th>
                                    <th>Rank</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                 </thead>
                            <tbody>
                                @foreach($attempts as $index => $attempt)
                                    @php
                                        $percentage = $attempt->quiz->total_points > 0 
                                            ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
                                            : 0;
                                        $rank = \App\Models\Leaderboard::where('quiz_id', $attempt->quiz_id)
                                            ->where('user_id', $attempt->user_id)
                                            ->value('rank');
                                        
                                        $rankClass = '';
                                        if($rank == 1) $rankClass = 'rank-1';
                                        elseif($rank == 2) $rankClass = 'rank-2';
                                        elseif($rank == 3) $rankClass = 'rank-3';
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $attempts->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    {{ strtoupper(substr($attempt->user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $attempt->user->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $attempt->user->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ Str::limit($attempt->quiz->title, 40) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $attempt->quiz->category->name ?? 'Uncategorized' }}</small>
                                        </td>
                                        <td class="text-center fw-bold">
                                            {{ $attempt->score }}
                                        </td>
                                        <td class="text-center">
                                            {{ $attempt->quiz->total_points }}
                                        </td>
                                        <td class="text-center" style="width: 100px;">
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar {{ $percentage >= $attempt->quiz->passing_score ? 'bg-success' : 'bg-warning' }}" 
                                                     style="width: {{ $percentage }}%">
                                                    {{ $percentage }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($rank)
                                                <span class="badge {{ $rankClass }} p-2" style="min-width: 50px;">
                                                    @if($rank == 1) 🥇
                                                    @elseif($rank == 2) 🥈
                                                    @elseif($rank == 3) 🥉
                                                    @endif
                                                    #{{ $rank }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($attempt->result && $attempt->result->passed)
                                                <span class="status-badge status-passed">
                                                    <i class="fas fa-check-circle"></i> Passed
                                                </span>
                                            @else
                                                <span class="status-badge status-failed">
                                                    <i class="fas fa-times-circle"></i> Failed
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $attempt->created_at->format('M d, Y') }}
                                            <br>
                                            <small class="text-muted">{{ $attempt->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.results.show', $attempt) }}" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $attempts->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                        <h5>No Results Found</h5>
                        <p class="text-muted">
                            @if(!Auth::user()->isMasterAdmin())
                                No quiz attempts found for your quizzes.
                            @else
                                No quiz attempts found matching your criteria.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Export button handler
        $('#exportBtn').on('click', function() {
            const formData = $('#filterForm').serialize();
            window.location.href = '{{ route("admin.results.export") }}?' + formData;
        });
        
        // Auto-submit when filter changes
        $('#quiz_id, #user_id, #passed').on('change', function() {
            $('#filterForm').submit();
        });
        
        // Initialize DataTable for sorting and searching
        if ($('#resultsTable').length) {
            $('#resultsTable').DataTable({
                pageLength: 25,
                responsive: true,
                ordering: true,
                searching: true,
                paging: false,
                info: false,
                columnDefs: [
                    { orderable: false, targets: [9] }
                ],
                language: {
                    search: "Search users or quizzes:"
                }
            });
        }
    });
</script>
@endpush
@endsection