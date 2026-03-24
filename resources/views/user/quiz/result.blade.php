@extends('layouts.app')

@section('title', 'Quiz Result - ' . $attempt->quiz->title)

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow">
            <div class="card-header text-center {{ $result->passed ? 'bg-success' : 'bg-warning' }} text-white">
                <h3 class="mb-0">
                    @if($result->passed)
                        <i class="fas fa-trophy"></i> Congratulations! You Passed!
                    @else
                        <i class="fas fa-frown"></i> Better Luck Next Time!
                    @endif
                </h3>
            </div>
            <div class="card-body text-center">
                <!-- Score Circle -->
                <div class="position-relative d-inline-block mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                         style="width: 150px; height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div>
                            <h1 class="display-1 text-white mb-0">{{ $result->percentage }}%</h1>
                            <p class="text-white mb-0">Score</p>
                        </div>
                    </div>
                </div>
                
                <p class="lead">Your Score: <strong>{{ $attempt->score }}</strong> / {{ $attempt->quiz->total_points }}</p>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-success">{{ $attempt->correct_answers }}</h3>
                            <p class="text-muted mb-0">Correct Answers</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-danger">{{ $attempt->incorrect_answers }}</h3>
                            <p class="text-muted mb-0">Incorrect Answers</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="text-info">{{ $result->rank ?? 'N/A' }}</h3>
                            <p class="text-muted mb-0">Your Rank</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row text-start">
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle"></i> Quiz Details</h5>
                        <table class="table table-sm">
                            <tr>
                                <td>Quiz Title:</td>
                                <td><strong>{{ $attempt->quiz->title }}</strong></td>
                            </tr>
                            <tr>
                                <td>Category:</td>
                                <td>{{ $attempt->quiz->category->name }}</td>
                            </tr>
                            <tr>
                                <td>Started:</td>
                                <td>{{ $attempt->started_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <td>Completed:</td>
                                <td>{{ $attempt->ended_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <td>Time Taken:</td>
                                <td>{{ $attempt->ended_at->diffInMinutes($attempt->started_at) }} minutes</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-chart-line"></i> Performance</h5>
                        <table class="table table-sm">
                            <tr>
                                <td>Total Questions:</td>
                                <td><strong>{{ $attempt->total_questions }}</strong></td>
                            </tr>
                            <tr>
                                <td>Correct Answers:</td>
                                <td><span class="text-success">{{ $attempt->correct_answers }}</span></td>
                            </tr>
                            <tr>
                                <td>Incorrect Answers:</td>
                                <td><span class="text-danger">{{ $attempt->incorrect_answers }}</span></td>
                            </tr>
                            <tr>
                                <td>Accuracy:</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $result->passed ? 'bg-success' : 'bg-warning' }}" 
                                             style="width: {{ $attempt->total_questions > 0 ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1) : 0 }}%">
                                            {{ $attempt->total_questions > 0 ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1) : 0 }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Points Earned:</td>
                                <td><strong class="text-primary">{{ $attempt->score }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($result->passed)
                    <div class="mt-4">
                        <a href="{{ route('user.certificate', $attempt) }}" class="btn btn-success btn-lg">
                            <i class="fas fa-certificate"></i> Download Certificate
                        </a>
                    </div>
                @endif

                <hr>

                <div class="mt-3">
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                    <a href="{{ route('user.quiz.lobby', $attempt->quiz) }}" class="btn btn-outline-primary">
                        <i class="fas fa-redo"></i> Retake Quiz
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection