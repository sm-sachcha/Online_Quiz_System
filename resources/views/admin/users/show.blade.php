@extends('layouts.admin')

@section('title', 'User Details - ' . $user->name)

@section('content')
<div class="row">
    <!-- User Information Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-circle"></i> User Information</h5>
            </div>
            <div class="card-body text-center">
                @if($user->profile && $user->profile->profile_picture)
                    <img src="{{ asset('storage/' . $user->profile->profile_picture) }}" 
                         alt="{{ $user->name }}" 
                         class="rounded-circle img-fluid mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                @else
                    <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 150px; height: 150px; font-size: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                
                <h4>{{ $user->name }}</h4>
                <p class="text-muted">
                    <i class="fas fa-envelope"></i> {{ $user->email }}
                </p>
                
                <div class="row mt-3">
                    <div class="col-4">
                        <h5 class="text-primary">{{ $user->profile->total_points ?? 0 }}</h5>
                        <small class="text-muted">Total Points</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-success">{{ $user->profile->quizzes_attempted ?? 0 }}</h5>
                        <small class="text-muted">Attempts</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-warning">{{ $user->profile->quizzes_won ?? 0 }}</h5>
                        <small class="text-muted">Won</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-start">
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-phone"></i> Phone:</td>
                            <td><strong>{{ $user->profile->phone ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-home"></i> Address:</td>
                            <td><strong>{{ $user->profile->address ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-city"></i> City:</td>
                            <td><strong>{{ $user->profile->city ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-globe"></i> Country:</td>
                            <td><strong>{{ $user->profile->country ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-calendar-alt"></i> Joined:</td>
                            <td><strong>{{ $user->created_at->format('M d, Y') }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clock"></i> Status:</td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit User
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3>{{ $stats['total_attempts'] }}</h3>
                                <p class="mb-0">Total Attempts</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3>{{ number_format($stats['average_score'], 1) }}</h3>
                                <p class="mb-0">Avg Score</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3>{{ $stats['completed_quizzes'] }}</h3>
                                <p class="mb-0">Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3>{{ $stats['passed_quizzes'] }}</h3>
                                <p class="mb-0">Passed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Quiz Attempts -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Quiz Attempts</h5>
            </div>
            <div class="card-body">
                @if($recentAttempts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Quiz Title</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAttempts as $attempt)
                                    <tr>
                                        <td>
                                            <strong>{{ $attempt->quiz->title }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $attempt->score }} / {{ $attempt->quiz->total_points }}</span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                @php
                                                    $percentage = $attempt->quiz->total_points > 0 
                                                        ? ($attempt->score / $attempt->quiz->total_points) * 100 
                                                        : 0;
                                                @endphp
                                                <div class="progress-bar {{ $percentage >= $attempt->quiz->passing_score ? 'bg-success' : 'bg-warning' }}" 
                                                     style="width: {{ $percentage }}%">
                                                    {{ round($percentage, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($attempt->status == 'completed')
                                                @if($attempt->result && $attempt->result->passed)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Passed
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times"></i> Failed
                                                    </span>
                                                @endif
                                            @elseif($attempt->status == 'in_progress')
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-spinner"></i> In Progress
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    {{ ucfirst($attempt->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $attempt->created_at->format('M d, Y') }}</small>
                                            <br>
                                            <small class="text-muted">{{ $attempt->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('user.quiz.result', ['quiz' => $attempt->quiz_id, 'attempt' => $attempt->id]) }}" 
                                               class="btn btn-sm btn-info" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
@endsection