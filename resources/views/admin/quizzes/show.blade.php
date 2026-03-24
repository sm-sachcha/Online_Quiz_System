@extends('layouts.admin')

@section('title', 'Quiz Details - ' . $quiz->title)

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Quiz Details</h5>
                <div>
                    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Quiz
                    </a>
                    <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-question-circle"></i> Manage Questions
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>{{ $quiz->title }}</h4>
                        <p class="text-muted">{{ $quiz->description ?: 'No description provided.' }}</p>
                        
                        <table class="table table-bordered">
                             <tr>
                                <th style="width: 150px;">Category:</th>
                                <td>
                                    @if($quiz->category->icon)
                                        <i class="{{ $quiz->category->icon }}"></i>
                                    @endif
                                    <strong>{{ $quiz->category->name }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <th>Duration:</th>
                                <td>{{ $quiz->duration_minutes }} minutes</td>
                            </tr>
                            <tr>
                                <th>Total Questions:</th>
                                <td><span class="badge bg-info">{{ $quiz->questions->count() }}</span></td>
                            </tr>
                            <tr>
                                <th>Total Points:</th>
                                <td><span class="badge bg-success">{{ $quiz->questions->sum('points') }}</span></td>
                            </tr>
                            <tr>
                                <th>Passing Score:</th>
                                <td>{{ $quiz->passing_score }}%</td>
                            </tr>
                            <tr>
                                <th>Max Attempts:</th>
                                <td>{{ $quiz->max_attempts }}</td>
                            </tr>
                            <tr>
                                <th>Random Order:</th>
                                <td>{{ $quiz->is_random_questions ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($quiz->is_published)
                                        <span class="badge bg-success">Published</span>
                                    @else
                                        <span class="badge bg-secondary">Hidden</span>
                                    @endif
                                </td>
                            </tr>
                            @if($quiz->scheduled_at)
                            <tr>
                                <th>Scheduled Start:</th>
                                <td>{{ $quiz->scheduled_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            @endif
                            @if($quiz->ends_at)
                            <tr>
                                <th>Ends At:</th>
                                <td>{{ $quiz->ends_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $quiz->creator->name }} ({{ $quiz->created_at->format('M d, Y') }})</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-chart-line"></i> Statistics</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $stats['total_attempts'] }}</h3>
                                        <p class="mb-0">Total Attempts</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ number_format($stats['average_score'], 1) }}</h3>
                                        <p class="mb-0">Average Score</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ number_format($stats['completion_rate'], 1) }}%</h3>
                                        <p class="mb-0">Completion Rate</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3>{{ $stats['total_points'] }}</h3>
                                        <p class="mb-0">Total Points</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Questions List</h5>
            </div>
            <div class="card-body">
                @if($quiz->questions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                 <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Points</th>
                                    <th>Time (sec)</th>
                                    <th>Options</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quiz->questions as $index => $question)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ Str::limit($question->question_text, 60) }}</td>
                                        <td>
                                            @if($question->question_type == 'multiple_choice')
                                                <span class="badge bg-primary">Multiple Choice</span>
                                            @elseif($question->question_type == 'true_false')
                                                <span class="badge bg-info">True/False</span>
                                            @else
                                                <span class="badge bg-secondary">Single Choice</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-success">{{ $question->points }}</span></td>
                                        <td><span class="badge bg-warning">{{ $question->time_seconds }}s</span></td>
                                        <td>{{ $question->options->count() }}</td>
                                        <td>
                                            <a href="{{ route('admin.quizzes.questions.edit', [$quiz, $question]) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <p>No questions added yet.</p>
                        <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Question
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection