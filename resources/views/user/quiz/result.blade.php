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
    .category-badge {
        background-color: #6c757d;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .category-badge-public {
        background-color: #17a2b8;
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
                    @if(isset($attemptNumber) && $attemptNumber > 1)
                        <small class="mt-2 d-block">
                            <i class="fas fa-redo"></i> Attempt #{{ $attemptNumber }}
                            @if(isset($isBestScore) && $isBestScore)
                                <span class="badge bg-warning text-dark ms-2 improvement-badge">
                                    <i class="fas fa-chart-line"></i> New Best Score!
                                </span>
                            @endif
                        </small>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Score Circle -->
                        <div class="col-md-4 text-center mb-4">
                            <div class="score-circle">
                                <div class="score-number" id="resultPercentage">{{ $percentage ?? 0 }}%</div>
                                <div class="score-label">Your Score</div>
                            </div>
                            <div class="mt-3">
                                <h4><span id="resultScore">{{ $attempt->score }}</span> / {{ $attempt->quiz->total_points }} points</h4>
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
                                    <div class="rank-number" id="resultRank">
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
                                    <div class="rank-label">Rank</div>
                                    <div class="mt-2">
                                        <small>Out of <span id="resultTotalParticipants">{{ $totalParticipants ?? 0 }}</span> participants</small>
                                    </div>
                                    <!-- <div class="mt-1">
                                        <small id="resultTopPercent">Top {{ ($totalParticipants ?? 0) > 0 ? round(($userRank / ($totalParticipants ?? 1)) * 100, 1) : 0 }}%</small>
                                    </div> -->
                                </div>
                            @else
                                <div class="rank-card" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                                    <div class="rank-number" id="resultRank">N/A</div>
                                    <div class="rank-label">Rank</div>
                                    <div class="mt-2">
                                        <small>Out of <span id="resultTotalParticipants">{{ $totalParticipants ?? 0 }}</span> participants</small>
                                    </div>
                                    <div class="mt-1"><small id="resultTopPercent">Unranked</small></div>
                                </div>
                            @endif
                        </div>

                        <!-- Performance Summary -->
                        <div class="col-md-4">
                            <div class="stat-box mb-2">
                                <div class="stat-number text-success" id="resultCorrectAnswers">{{ $attempt->correct_answers }}</div>
                                <div>Correct Answers</div>
                            </div>
                            <div class="stat-box mb-2">
                                <div class="stat-number text-danger" id="resultIncorrectAnswers">{{ $attempt->incorrect_answers }}</div>
                                <div>Incorrect Answers</div>
                            </div>
                            <!-- <div class="stat-box">
                                <div class="stat-number text-info" id="resultAccuracy">{{ $performanceMetrics['accuracy'] ?? 0 }}%</div>
                                <div>Accuracy</div>
                                <small class="text-muted"><span id="resultAccuracyBreakdown">{{ $attempt->correct_answers }}/{{ $attempt->total_questions }}</span> questions</small>
                            </div> -->
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
                                    <td>
                                        @if($attempt->quiz->category)
                                            <span class="category-badge" style="background-color: {{ $attempt->quiz->category->color ?? '#6c757d' }}">
                                                <i class="{{ $attempt->quiz->category->icon ?? 'fas fa-tag' }}"></i>
                                                {{ $attempt->quiz->category->name }}
                                            </span>
                                        @else
                                            <span class="category-badge category-badge-public">
                                                <i class="fas fa-globe"></i> Instant Quiz
                                            </span>
                                        @endif
                                    </td>
                                  </tr>
                                  <tr>
                                    <td width="40%"><strong>Started:</strong></td>
                                    <td>{{ optional($performanceMetrics['quiz_started_at'] ?? null)?->format('M d, Y h:i A') ?? $attempt->started_at->format('M d, Y h:i A') }}</td>
                                  </tr>
                                  <tr>
                                    <td width="40%"><strong>Completed:</strong></td>
                                    <td>{{ $attempt->ended_at->format('M d, Y h:i A') }}</td>
                                  </tr>
                                  <tr>
                                    <td width="40%"><strong>Time Taken:</strong></td>
                                    <td>
                                        @php
                                            $totalSeconds = abs($performanceMetrics['time_taken'] ?? 0);
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
                                            $avgSeconds = abs($performanceMetrics['time_per_question'] ?? 0);
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
                                            <div class="progress-bar {{ ($performanceMetrics['accuracy'] ?? 0) >= 60 ? 'bg-success' : 'bg-warning' }}" 
                                                 style="width: {{ $performanceMetrics['accuracy'] ?? 0 }}%">
                                                {{ $performanceMetrics['accuracy'] ?? 0 }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $attempt->correct_answers }}/{{ $attempt->total_questions }} correct</small>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td width="40%"><strong>Passing Score:</strong></td>
                                    <td>{{ $attempt->quiz->passing_score }}%</td>
                                  </tr>
                                  <tr>
                                    <td width="40%"><strong>Your Score:</strong></td>
                                    <td><strong class="text-primary">{{ $percentage ?? 0 }}%</strong></td>
                                  </tr>
                              </table>
                        </div>
                    </div>

                    <!-- Attempts Info Section -->
                    <div class="attempts-info text-center">
                        <div class="row">
                            <!-- <div class="col-md-4">
                                <i class="fas fa-bullseye text-primary"></i>
                                <strong>Current Attempt: {{ $attempt->score }} points</strong>
                                <br><small class="text-muted">({{ $percentage ?? 0 }}%)</small>
                            </div> -->
                            <!-- <div class="col-md-4">
                                <i class="fas fa-chart-line text-success"></i>
                                @if(isset($bestScoreInfo) && $bestScoreInfo)
                                    <strong>Best Score: {{ $bestScoreInfo['score'] }} points</strong>
                                    <br><small class="text-muted">({{ $bestScoreInfo['percentage'] ?? 0 }}%)</small>
                                @else
                                    <strong>Best Score: This attempt</strong>
                                    <br><small class="text-muted">{{ $attempt->score }} points ({{ $percentage ?? 0 }}%)</small>
                                @endif
                            </div> -->
                            <!-- <div class="col-md-4">
                                <i class="fas fa-bullseye text-info"></i>
                                <strong>Current Accuracy: {{ $performanceMetrics['accuracy'] ?? 0 }}%</strong>
                                <br><small class="text-muted">{{ $attempt->correct_answers }}/{{ $attempt->total_questions }} correct</small>
                            </div> -->
                        </div>
                    </div>

                    <!-- Remaining Attempts Info
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
                            Your best score: <strong>{{ $bestScoreInfo['score'] ?? $attempt->score }} points ({{ $bestScoreInfo['percentage'] ?? $percentage ?? 0 }}%)</strong>
                        </div>
                    @endif -->
                </div>
            </div>

            <!-- Leaderboard Top 10 -->
            @if(isset($topLeaderboard) && $topLeaderboard->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 Leaderboard</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div id="resultLeaderboardList">
                            @foreach($topLeaderboard as $entry)
                                @php
                                    // Handle different data structures - entry could be object or array
                                    $rank = is_array($entry) ? ($entry['rank'] ?? null) : ($entry->rank ?? null);
                                    $score = is_array($entry) ? ($entry['score'] ?? 0) : ($entry->score ?? 0);
                                    $percentage = is_array($entry) ? ($entry['percentage'] ?? null) : ($entry->percentage ?? null);
                                    
                                    // Get user name - handle different structures
                                    $userName = 'Unknown';
                                    $userId = null;
                                    $isCurrentUser = false;
                                    
                                    if (is_array($entry)) {
                                        // Array structure
                                        $userName = $entry['user_name'] ?? ($entry['name'] ?? 'Unknown');
                                        $userId = $entry['user_id'] ?? null;
                                    } else {
                                        // Object structure
                                        if (isset($entry->user) && $entry->user) {
                                            $userName = $entry->user->name;
                                            $userId = $entry->user->id;
                                        } elseif (isset($entry->name)) {
                                            $userName = $entry->name;
                                        } elseif (isset($entry->user_name)) {
                                            $userName = $entry->user_name;
                                        }
                                        $userId = $entry->user_id ?? null;
                                    }
                                    
                                    // Check if this is the current user
                                    if (Auth::check() && $userId && $userId == Auth::id()) {
                                        $isCurrentUser = true;
                                    }
                                    
                                    // Get rank display
                                    $rankDisplay = '#';
                                    if ($rank == 1) {
                                        $rankDisplay = '🥇';
                                    } elseif ($rank == 2) {
                                        $rankDisplay = '🥈';
                                    } elseif ($rank == 3) {
                                        $rankDisplay = '🥉';
                                    } else {
                                        $rankDisplay = "#{$rank}";
                                    }
                                @endphp
                                <div class="list-group-item leaderboard-item {{ $isCurrentUser ? 'current-user-rank' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="{{ $rank == 1 ? 'medal-gold' : ($rank == 2 ? 'medal-silver' : ($rank == 3 ? 'medal-bronze' : '')) }} fs-4 me-2">
                                                {{ $rankDisplay }}
                                            </span>
                                            <strong class="ms-1">{{ $userName }}</strong>
                                            @if($isCurrentUser)
                                                <span class="badge bg-success ms-2">You</span>
                                            @endif
                                            @if($percentage)
                                                <small class="text-muted ms-2">({{ $percentage }}%)</small>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="badge bg-primary">{{ $score }} pts</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </div>
                        </div>
                        @if(isset($totalParticipants) && $totalParticipants > 10)
                            <div class="card-footer text-center">
                                <small class="text-muted">And {{ $totalParticipants - 10 }} more participants...</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const resultQuizId = {{ $attempt->quiz_id }};
    const resultAttemptId = {{ $attempt->id }};
    const resultAttemptUserId = {{ $attempt->user_id ?? 'null' }};
    const resultAttemptParticipantId = {{ $attempt->participant_id ?? 'null' }};
    sessionStorage.removeItem('joined_quiz_' + resultQuizId);
    sessionStorage.removeItem('guest_name_' + resultQuizId);
    sessionStorage.removeItem('participant_id_' + resultQuizId);

    function shareResult() {
        const url = window.location.href;
        const text = `I scored {{ $percentage ?? 0 }}% on "{{ $attempt->quiz->title }}" and ranked #{{ $userRank ?? 'N/A' }}! Can you beat my score?`;
        
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

    function getRankDisplay(rank) {
        if (rank === 1) return '🥇 #1';
        if (rank === 2) return '🥈 #2';
        if (rank === 3) return '🥉 #3';
        return rank ? `#${rank}` : 'N/A';
    }

    function updateResultSummary(payload) {
        if (typeof payload.score !== 'undefined') {
            const scoreEl = document.getElementById('resultScore');
            if (scoreEl) scoreEl.textContent = payload.score;
        }

        if (typeof payload.percentage !== 'undefined') {
            const percentageEl = document.getElementById('resultPercentage');
            if (percentageEl) percentageEl.textContent = `${payload.percentage}%`;
        }

        if (typeof payload.correct_answers !== 'undefined') {
            const correctEl = document.getElementById('resultCorrectAnswers');
            if (correctEl) correctEl.textContent = payload.correct_answers;
        }

        if (typeof payload.incorrect_answers !== 'undefined') {
            const incorrectEl = document.getElementById('resultIncorrectAnswers');
            if (incorrectEl) incorrectEl.textContent = payload.incorrect_answers;
        }

        if (typeof payload.correct_answers !== 'undefined' && typeof payload.incorrect_answers !== 'undefined') {
            const totalAnswered = Number(payload.correct_answers) + Number(payload.incorrect_answers);
            const accuracy = totalAnswered > 0 ? ((Number(payload.correct_answers) / totalAnswered) * 100).toFixed(1) : '0.0';
            const accuracyEl = document.getElementById('resultAccuracy');
            const accuracyBreakdownEl = document.getElementById('resultAccuracyBreakdown');

            if (accuracyEl) accuracyEl.textContent = `${accuracy}%`;
            if (accuracyBreakdownEl) accuracyBreakdownEl.textContent = `${payload.correct_answers}/${totalAnswered}`;
        }

        if (typeof payload.rank !== 'undefined') {
            const rankEl = document.getElementById('resultRank');
            if (rankEl) rankEl.textContent = getRankDisplay(payload.rank);
        }

        if (typeof payload.total_participants !== 'undefined') {
            const totalParticipantsEl = document.getElementById('resultTotalParticipants');
            if (totalParticipantsEl) totalParticipantsEl.textContent = payload.total_participants;

            const topPercentEl = document.getElementById('resultTopPercent');
            if (topPercentEl) {
                if (payload.rank && payload.total_participants > 0) {
                    topPercentEl.textContent = `Top ${((payload.rank / payload.total_participants) * 100).toFixed(1)}%`;
                } else {
                    topPercentEl.textContent = 'Unranked';
                }
            }
        }
    }

    function renderLeaderboard(leaderboard) {
        const list = document.getElementById('resultLeaderboardList');
        if (!list || !Array.isArray(leaderboard)) return;

        list.innerHTML = leaderboard.slice(0, 10).map((entry) => {
            const rank = Number(entry.rank);
            const rankDisplay = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : `#${rank}`;
            const isCurrentUser =
                (resultAttemptUserId && Number(entry.user_id) === Number(resultAttemptUserId)) ||
                (resultAttemptParticipantId && Number(entry.participant_id) === Number(resultAttemptParticipantId));

            return `
                <div class="list-group-item leaderboard-item ${isCurrentUser ? 'current-user-rank' : ''}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="${rank === 1 ? 'medal-gold' : (rank === 2 ? 'medal-silver' : (rank === 3 ? 'medal-bronze' : ''))} fs-4 me-2">
                                ${rankDisplay}
                            </span>
                            <strong class="ms-1">${entry.name ?? 'Unknown'}</strong>
                            ${isCurrentUser ? '<span class="badge bg-success ms-2">You</span>' : ''}
                            <small class="text-muted ms-2">(${entry.percentage ?? 0}%)</small>
                        </div>
                        <div>
                            <span class="badge bg-primary">${entry.score ?? 0} pts</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    if (typeof window.initializeEcho === 'function') {
        window.initializeEcho(resultQuizId, {
            onLeaderboardUpdated(event) {
                const leaderboard = Array.isArray(event.leaderboard) ? event.leaderboard : [];
                renderLeaderboard(leaderboard);

                const currentEntry = leaderboard.find((entry) =>
                    (resultAttemptUserId && Number(entry.user_id) === Number(resultAttemptUserId)) ||
                    (resultAttemptParticipantId && Number(entry.participant_id) === Number(resultAttemptParticipantId))
                );

                if (currentEntry) {
                    updateResultSummary({
                        rank: currentEntry.rank,
                        total_participants: leaderboard.length,
                    });
                }
            }
        });
    }

    if (typeof window.initializeAttemptEcho === 'function') {
        window.initializeAttemptEcho(resultAttemptId, {
            onAttemptResultUpdated(event) {
                updateResultSummary(event.payload || {});
            }
        });
    }
</script>
@endpush
@endsection
