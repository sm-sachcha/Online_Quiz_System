@extends('layouts.app')

@section('title', 'Quiz Result - ' . $attempt->quiz->title)

@section('content')
<style>
    .result-card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .score-circle {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .score-number {
        font-size: 48px;
        font-weight: bold;
    }
    .rank-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        color: white;
    }
    .rank-number {
        font-size: 48px;
        font-weight: bold;
    }
    .leaderboard-item {
        transition: all 0.3s;
    }
    .leaderboard-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
    .current-user-rank {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
        font-weight: bold;
    }
    .medal-gold { color: #ffd700; }
    .medal-silver { color: #c0c0c0; }
    .medal-bronze { color: #cd7f32; }
    .stat-box {
        text-align: center;
        padding: 15px;
        border-radius: 10px;
        background: #f8f9fa;
        transition: all 0.3s;
    }
    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 28px;
        font-weight: bold;
    }
    .improvement-badge {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
    }
    .best-score-badge {
        background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%);
        color: #333;
        padding: 8px 16px;
        border-radius: 25px;
        display: inline-block;
        font-weight: bold;
    }
    .attempts-info {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 12px 20px;
        margin-top: 15px;
    }
    .time-display {
        font-family: monospace;
        font-size: 16px;
        font-weight: bold;
    }
    .question-time {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        background-color: #e9ecef;
        font-family: monospace;
    }
    .time-exceeded {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <!-- Header Card -->
            <div class="card result-card mb-4">
                <div class="card-header text-center {{ $result->passed ? 'bg-success' : 'bg-warning' }} text-white">
                    <h3 class="mb-0">
                        @if($result->passed)
                            <i class="fas fa-trophy"></i> Congratulations! You Passed!
                        @else
                            <i class="fas fa-frown"></i> Better Luck Next Time!
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Score Circle -->
                        <div class="col-md-4 text-center mb-4">
                            <div class="score-circle">
                                <div class="score-number">{{ $percentage }}%</div>
                                <div class="score-label">Your Score</div>
                            </div>
                            <div class="mt-3">
                                <h4>{{ $attempt->score }} / {{ $attempt->quiz->total_points }} points</h4>
                                @if(isset($isBestScore) && $isBestScore)
                                    <div class="best-score-badge mt-2">
                                        <i class="fas fa-star"></i> Your Best Score!
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Rank Card -->
                        <div class="col-md-4 text-center mb-4">
                            @if(isset($userRank) && $userRank)
                                <div class="rank-card">
                                    <div class="rank-number">
                                        @if($userRank == 1)
                                            🥇 #1
                                        @elseif($userRank == 2)
                                            🥈 #2
                                        @elseif($userRank == 3)
                                            🥉 #3
                                        @else
                                            #{{ $userRank }}
                                        @endif
                                    </div>
                                    <div class="rank-label">Your Rank</div>
                                    <div class="mt-2">
                                        <small>Out of {{ $totalParticipants }} participants</small>
                                    </div>
                                    <div class="mt-1">
                                        <small>Top {{ $totalParticipants > 0 ? round(($userRank / $totalParticipants) * 100, 1) : 0 }}%</small>
                                    </div>
                                </div>
                            @else
                                <div class="rank-card" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                                    <div class="rank-number">N/A</div>
                                    <div class="rank-label">Rank</div>
                                    <div class="mt-2">
                                        <small>Complete more quizzes to get ranked!</small>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Performance Summary -->
                        <div class="col-md-4">
                            <div class="stat-box mb-2">
                                <div class="stat-number text-success">{{ $attempt->correct_answers }}</div>
                                <div>Correct Answers</div>
                            </div>
                            <div class="stat-box mb-2">
                                <div class="stat-number text-danger">{{ $attempt->incorrect_answers }}</div>
                                <div>Incorrect Answers</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number text-info">{{ $performanceMetrics['accuracy'] }}%</div>
                                <div>Accuracy</div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Performance Details -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle text-primary"></i> Quiz Details</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Quiz Title:</strong></td>
                                    <td><strong>{{ $attempt->quiz->title }}</strong></td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Category:</strong></td>
                                    <td>{{ $attempt->quiz->category->name }}</td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Started:</strong></td>
                                    <td>{{ $attempt->started_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Completed:</strong></td>
                                    <td>{{ $attempt->ended_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Time Taken:</strong></td>
                                    <td>
                                        @php
                                            $totalSeconds = abs($performanceMetrics['time_taken']);
                                            $minutes = floor($totalSeconds / 60);
                                            $seconds = $totalSeconds % 60;
                                            $timeDisplay = $minutes > 0 ? "{$minutes} min {$seconds} sec" : "{$seconds} sec";
                                        @endphp
                                        <span class="time-display text-primary">{{ $timeDisplay }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Avg Time/Question:</strong></td>
                                    <td>
                                        @php
                                            $avgSeconds = abs($performanceMetrics['time_per_question']);
                                            $avgMinutes = floor($avgSeconds / 60);
                                            $avgSecs = $avgSeconds % 60;
                                            $avgDisplay = $avgMinutes > 0 ? "{$avgMinutes} min {$avgSecs} sec" : "{$avgSecs} sec";
                                        @endphp
                                        {{ $avgDisplay }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-line text-primary"></i> Performance Summary</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Total Questions:</strong></td>
                                    <td>{{ $attempt->total_questions }}</td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Correct Answers:</strong></td>
                                    <td class="text-success"><strong>{{ $attempt->correct_answers }}</strong></td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Incorrect Answers:</strong></td>
                                    <td class="text-danger"><strong>{{ $attempt->incorrect_answers }}</strong></td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Accuracy:</strong></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $performanceMetrics['accuracy'] >= 60 ? 'bg-success' : 'bg-warning' }}" 
                                                 style="width: {{ $performanceMetrics['accuracy'] }}%">
                                                {{ $performanceMetrics['accuracy'] }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Passing Score:</strong></td>
                                    <td>{{ $attempt->quiz->passing_score }}%</td>
                                </tr>
                                <tr>
                                    <td width="40%"><strong>Your Score:</strong></td>
                                    <td><strong class="text-primary">{{ $percentage }}%</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Attempts Info Section (Simple) -->
                    <div class="attempts-info text-center">
                        <div class="row">
                            <div class="col-md-6">
                                <i class="fas fa-redo text-primary"></i>
                                <strong>Attempt #{{ $attemptNumber ?? 1 }}</strong>
                                @if(isset($totalAttempts) && $totalAttempts > 1)
                                    <br><small class="text-muted">({{ $totalAttempts }} total attempts)</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <i class="fas fa-chart-line text-success"></i>
                                <strong>Best Score: {{ $bestScoreInfo['score'] ?? $attempt->score }} points</strong>
                                @if(isset($bestScoreInfo) && $bestScoreInfo && $bestScoreInfo['score'] != $attempt->score)
                                    <br><small class="text-muted">({{ $bestScoreInfo['percentage'] }}%)</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Remaining Attempts Info -->
                    @if(isset($remainingAttempts) && $remainingAttempts > 0)
                        <div class="alert alert-info text-center mt-3">
                            <i class="fas fa-info-circle"></i>
                            You have <strong>{{ $remainingAttempts }}</strong> attempt(s) remaining. 
                            Only your best score will be shown on the leaderboard.
                        </div>
                    @elseif(isset($remainingAttempts) && $remainingAttempts == 0 && isset($totalAttempts) && $totalAttempts > 0)
                        <div class="alert alert-warning text-center mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            You have used all {{ $attempt->quiz->max_attempts }} attempts for this quiz.
                            Your best score: <strong>{{ $bestScoreInfo['score'] ?? $attempt->score }} points ({{ $bestScoreInfo['percentage'] ?? $percentage }}%)</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Leaderboard Top 10 -->
            @if($topLeaderboard->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 3 Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($topLeaderboard as $entry)
                                <div class="list-group-item leaderboard-item {{ $entry->user_id == Auth::id() ? 'current-user-rank' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            @if($entry->rank == 1)
                                                <span class="medal-gold fs-4">🥇</span>
                                            @elseif($entry->rank == 2)
                                                <span class="medal-silver fs-4">🥈</span>
                                            @elseif($entry->rank == 3)
                                                <span class="medal-bronze fs-4">🥉</span>
                                            @else
                                                <span class="badge bg-secondary">#{{ $entry->rank }}</span>
                                            @endif
                                            <strong class="ms-2">{{ $entry->user->name }}</strong>
                                            @if($entry->user_id == Auth::id())
                                                <span class="badge bg-success ms-2">You</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="badge bg-primary">{{ $entry->score }} pts</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($totalParticipants > 10)
                            <div class="card-footer text-center">
                                <small class="text-muted">And {{ $totalParticipants - 10 }} more participants...</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="text-center mb-5">
                <div class="d-flex justify-content-center gap-3 flex-wrap">

                    
                    @if(isset($remainingAttempts) && $remainingAttempts > 0)
                        <a href="{{ route('user.quiz.lobby', $attempt->quiz) }}" class="btn btn-warning btn-lg">
                            <i class="fas fa-redo"></i> Retake Quiz ({{ $remainingAttempts }} attempts left)
                        </a>
                    @endif
                    
                    <!-- <button class="btn btn-info btn-lg" onclick="shareResult()">
                        <i class="fas fa-share-alt"></i> Share Result
                    </button> -->
                    
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function shareResult() {
        const url = window.location.href;
        const text = `I scored {{ $percentage }}% on "{{ $attempt->quiz->title }}" and ranked #{{ $userRank ?? 'N/A' }}! Can you beat my score?`;
        
        if (navigator.share) {
            navigator.share({
                title: 'Quiz Result',
                text: text,
                url: url
            }).catch(console.error);
        } else {
            navigator.clipboard.writeText(text + ' ' + url).then(() => {
                alert('Result copied to clipboard! Share it with your friends.');
            });
        }
    }
    
    // Animate score
    const scoreNumber = document.querySelector('.score-number');
    if (scoreNumber) {
        const target = parseInt(scoreNumber.innerText);
        let current = 0;
        const interval = setInterval(() => {
            if (current <= target) {
                scoreNumber.innerText = current + '%';
                current++;
            } else {
                clearInterval(interval);
            }
        }, 20);
    }
</script>
@endpush
@endsection