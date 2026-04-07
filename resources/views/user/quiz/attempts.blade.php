@extends('layouts.app')

@section('title', 'My Attempts - ' . $quiz->title)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> My Attempts - {{ $quiz->title }}</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Maximum Attempts:</strong> {{ $quiz->max_attempts }}
                            </div>
                            <div class="col-md-4">
                                <strong>Attempts Used:</strong> {{ $attempts->where('status', 'completed')->count() }}
                            </div>
                            <div class="col-md-4">
                                <strong>Remaining Attempts:</strong> 
                                <span class="badge {{ $remainingAttempts > 0 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $remainingAttempts }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if($attempts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date & Time</th>
                                        <th>Score</th>
                                        <th>Correct/Total</th>
                                        <th>Percentage</th>
                                        <th>Time Remaining</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attempts as $index => $attempt)
                                        @php
                                            $percentage = $attempt->quiz->total_points > 0 
                                                ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1) 
                                                : 0;
                                            $timeTaken = $attempt->ended_at && $attempt->started_at 
                                                ? gmdate("i:s", $attempt->ended_at->diffInSeconds($attempt->started_at))
                                                : 'N/A';
                                        @endphp
                                        <tr class="{{ $attempt->status == 'completed' && $percentage >= $quiz->passing_score ? 'table-success' : '' }}">
                                            <td><strong>{{ $index + 1 }}</strong></td>
                                            <td>
                                                {{ $attempt->created_at->format('M d, Y h:i A') }}
                                                <br>
                                                <small class="text-muted">{{ $attempt->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $attempt->score }}</strong> / {{ $attempt->quiz->total_points }}
                                            </td>
                                            <td>
                                                {{ $attempt->correct_answers }}/{{ $attempt->total_questions }}
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" style="width: {{ ($attempt->correct_answers / max($attempt->total_questions, 1)) * 100 }}%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar {{ $percentage >= $quiz->passing_score ? 'bg-success' : 'bg-warning' }}" 
                                                         style="width: {{ $percentage }}%">
                                                        {{ $percentage }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-clock"></i> {{ $timeTaken }}
                                            </td>
                                            <td>
                                                @if($attempt->status == 'completed')
                                                    @if($percentage >= $quiz->passing_score)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Passed
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times-circle"></i> Failed
                                                        </span>
                                                    @endif
                                                @elseif($attempt->status == 'in_progress')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-play"></i> In Progress
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        {{ ucfirst($attempt->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($attempt->status == 'completed')
                                                    <a href="{{ route('user.quiz.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id]) }}" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    @if($percentage >= $quiz->passing_score)
                                                        <a href="{{ route('user.certificate', $attempt) }}" 
                                                           class="btn btn-sm btn-success">
                                                            <i class="fas fa-certificate"></i> Certificate
                                                        </a>
                                                    @endif
                                                @elseif($attempt->status == 'in_progress')
                                                    <a href="{{ route('user.quiz.attempt', ['quiz' => $quiz->id, 'attempt' => $attempt->id]) }}" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-play"></i> Resume
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3"></i>
                            <h5>No Attempts Yet</h5>
                            <p class="text-muted">You haven't taken this quiz yet.</p>
                        </div>
                    @endif
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('user.quiz.lobby', $quiz) }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Lobby
                        </a>
                        @if($remainingAttempts > 0 && !$attempts->where('status', 'in_progress')->first())
                            <a href="{{ route('user.quiz.start', $quiz) }}" class="btn btn-success">
                                <i class="fas fa-play"></i> Start New Attempt
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection