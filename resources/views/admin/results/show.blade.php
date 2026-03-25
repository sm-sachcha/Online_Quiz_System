@extends('layouts.admin')

@section('title', 'Result Details')

@section('content')
<style>
    .score-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .correct-answer {
        color: #28a745;
        font-weight: bold;
    }
    .incorrect-answer {
        color: #dc3545;
        font-weight: bold;
    }
    .avatar-lg {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 32px;
        margin: 0 auto;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line"></i> Result Details</h2>
            <div>
                <a href="{{ route('admin.results.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Results
                </a>
                <a href="{{ route('user.quiz.result', ['quiz' => $attempt->quiz_id, 'attempt' => $attempt->id]) }}" 
                   class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View as User
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- User Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> User Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar-lg mb-3">
                    {{ strtoupper(substr($attempt->user->name, 0, 1)) }}
                </div>
                <h4>{{ $attempt->user->name }}</h4>
                <p class="text-muted">{{ $attempt->user->email }}</p>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <strong>Total Points</strong>
                        <p>{{ $attempt->user->profile->total_points ?? 0 }}</p>
                    </div>
                    <div class="col-6">
                        <strong>Quizzes Taken</strong>
                        <p>{{ $attempt->user->quizAttempts()->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Quiz Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td width="40%"><strong>Quiz Title:</strong></td><td>{{ $attempt->quiz->title }}</td></tr>
                    <tr><td><strong>Category:</strong></td><td>{{ $attempt->quiz->category->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Duration:</strong></td><td>{{ $attempt->quiz->duration_minutes }} minutes</td></tr>
                    <tr><td><strong>Total Questions:</strong></td><td>{{ $attempt->total_questions }}</td></tr>
                    <tr><td><strong>Total Points:</strong></td><td>{{ $attempt->quiz->total_points }}</td></tr>
                    <tr><td><strong>Passing Score:</strong></td><td>{{ $attempt->quiz->passing_score }}%</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Result Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Result Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="score-circle mb-3">
                    <div class="score-number" style="font-size: 32px; font-weight: bold;">{{ $percentage }}%</div>
                    <div class="score-label">Score</div>
                </div>
                <h3>{{ $attempt->score }} / {{ $attempt->quiz->total_points }} points</h3>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <strong>Rank</strong>
                        <p>
                            @if($userRank)
                                @if($userRank == 1) 🥇 #1
                                @elseif($userRank == 2) 🥈 #2
                                @elseif($userRank == 3) 🥉 #3
                                @else #{{ $userRank }}
                                @endif
                                <br><small>out of {{ $totalParticipants }}</small>
                            @else N/A @endif
                        </p>
                    </div>
                    <div class="col-6">
                        <strong>Status</strong>
                        <p>@if($attempt->result && $attempt->result->passed) <span class="badge bg-success">PASSED</span> @else <span class="badge bg-danger">FAILED</span> @endif</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>Started</strong><p><small>{{ $attempt->started_at->format('M d, Y h:i A') }}</small></p></div>
                    <div class="col-6"><strong>Time Taken</strong><p>{{ gmdate("i:s", $timeTaken) }}</p></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question-wise Analysis -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Question-wise Analysis</h5>
    </div>
    <div class="card-body">
        <div class="accordion" id="questionAccordion">
            @foreach($attempt->answers as $index => $answer)
                @php
                    $question = $answer->question;
                    $isCorrect = $answer->is_correct;
                    $userOption = $answer->option;
                    $correctOption = $question->options->where('is_correct', true)->first();
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}">
                            <div class="d-flex justify-content-between w-100 me-3">
                                <span>
                                    @if($isCorrect) <i class="fas fa-check-circle text-success"></i> @else <i class="fas fa-times-circle text-danger"></i> @endif
                                    <strong>Question {{ $index + 1 }}:</strong> {{ Str::limit($question->question_text, 80) }}
                                </span>
                                <span class="badge {{ $isCorrect ? 'bg-success' : 'bg-danger' }}">{{ $answer->points_earned }}/{{ $question->points }} pts</span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#questionAccordion">
                        <div class="accordion-body">
                            <p><strong>Question:</strong> {{ $question->question_text }}</p>
                            <p><strong>User's Answer:</strong> 
                                @if($userOption) <span class="{{ $isCorrect ? 'correct-answer' : 'incorrect-answer' }}">{{ $userOption->option_text }}</span>
                                @else <span class="text-warning">Not answered</span> @endif
                            </p>
                            @if(!$isCorrect && $correctOption) <p class="text-success"><strong>Correct Answer:</strong> {{ $correctOption->option_text }}</p> @endif
                            @if($answer->time_taken_seconds) <p><strong>Time Taken:</strong> {{ $answer->time_taken_seconds }} seconds</p> @endif
                            @if($question->explanation) <div class="alert alert-info mt-2"><i class="fas fa-info-circle"></i> <strong>Explanation:</strong> {{ $question->explanation }}</div> @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection