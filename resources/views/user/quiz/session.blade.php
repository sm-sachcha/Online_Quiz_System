@extends('layouts.app')

@section('title', 'Quiz Session - ' . $quiz->title)

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h3><i class="fas fa-question-circle"></i> {{ $quiz->title }}</h3>
    </div>
    <div class="col-md-4 text-end">
        <span class="badge bg-primary fs-6">Question {{ $answeredCount + 1 }} of {{ $totalQuestions }}</span>
        <span class="badge bg-success fs-6 ms-2" id="score">Score: {{ $attempt->score }}</span>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="questionText">{{ $currentQuestion->question_text }}</h5>
                    <span class="badge bg-warning text-dark timer" id="timer">00:00</span>
                </div>
            </div>
            <div class="card-body">
                <div id="optionsContainer" class="row g-3">
                    @foreach($currentQuestion->options as $index => $option)
                        <div class="col-md-6">
                            <button class="btn btn-outline-primary w-100 option-btn text-start p-3" 
                                    data-option-id="{{ $option->id }}"
                                    data-option-index="{{ $index }}">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-secondary me-2">{{ chr(65 + $index) }}</span>
                                    {{ $option->option_text }}
                                </div>
                            </button>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Select an option to submit your answer
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-trophy"></i> Live Leaderboard</h5>
            </div>
            <div class="card-body p-0">
                <div id="leaderboard" class="list-group list-group-flush">
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Loading leaderboard...
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Your Progress</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: {{ ($answeredCount / $totalQuestions) * 100 }}%">
                        {{ $answeredCount }}/{{ $totalQuestions }}
                    </div>
                </div>
                <p class="mb-0">
                    <i class="fas fa-check-circle text-success"></i> Correct: {{ $attempt->correct_answers }} |
                    <i class="fas fa-times-circle text-danger"></i> Incorrect: {{ $attempt->incorrect_answers }}
                </p>
            </div>
        </div>
    </div>
</div>

<form id="submitAnswerForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="question_id" id="submitQuestionId">
    <input type="hidden" name="option_id" id="submitOptionId">
    <input type="hidden" name="time_taken" id="submitTimeTaken">
</form>

@push('scripts')
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    const quizId = {{ $quiz->id }};
    const attemptId = {{ $attempt->id }};
    const questionId = {{ $currentQuestion->id }};
    const timeSeconds = {{ $currentQuestion->time_seconds }};
    let timeLeft = timeSeconds;
    let timerInterval;
    let answerSubmitted = false;
    let startTime = Date.now();

    // Initialize Pusher
    const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
        cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            }
        }
    });

    const channel = pusher.subscribe('presence-quiz.' + quizId);

    channel.bind('leaderboard.updated', function(data) {
        updateLeaderboard(data.leaderboard);
    });

    channel.bind('answer.submitted', function(data) {
        // Visual feedback for other answers
        if (data.user_id !== {{ Auth::id() }}) {
            showNotification(`${data.user_name} answered!`, 'info');
        }
    });

    function updateLeaderboard(leaderboard) {
        const container = document.getElementById('leaderboard');
        if (!container) return;
        
        if (!leaderboard || leaderboard.length === 0) {
            container.innerHTML = '<div class="text-center py-3 text-muted">No scores yet</div>';
            return;
        }
        
        container.innerHTML = '';
        leaderboard.forEach((entry, index) => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center leaderboard-item';
            item.innerHTML = `
                <div>
                    <span class="badge bg-secondary me-2">${entry.rank}</span>
                    <strong>${entry.user_name}</strong>
                    ${entry.user_id === {{ Auth::id() }} ? '<span class="badge bg-info ms-2">You</span>' : ''}
                </div>
                <span class="badge bg-primary">${entry.score} pts</span>
            `;
            container.appendChild(item);
        });
    }

    function startTimer() {
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (!answerSubmitted) {
                    submitAnswer(null);
                }
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        const timerEl = document.getElementById('timer');
        timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft < 10) {
            timerEl.classList.add('timer-danger');
            timerEl.classList.add('bg-danger');
            timerEl.classList.remove('bg-warning');
        } else if (timeLeft < 30) {
            timerEl.classList.remove('timer-danger');
            timerEl.classList.add('bg-warning');
        }
    }

    function submitAnswer(optionId) {
        if (answerSubmitted) return;
        
        answerSubmitted = true;
        clearInterval(timerInterval);
        
        const timeTaken = Math.floor((Date.now() - startTime) / 1000);
        
        document.getElementById('submitQuestionId').value = questionId;
        document.getElementById('submitOptionId').value = optionId;
        document.getElementById('submitTimeTaken').value = timeTaken;
        
        const form = document.getElementById('submitAnswerForm');
        const formData = new FormData(form);
        
        fetch(`/user/quiz/attempt/${quizId}/${attemptId}/submit`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('score').textContent = 'Score: ' + data.current_score;
                
                // Highlight correct/incorrect answer
                document.querySelectorAll('.option-btn').forEach(btn => {
                    btn.disabled = true;
                    if (data.is_correct && btn.dataset.optionId == optionId) {
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-success');
                    } else if (!data.is_correct && btn.dataset.optionId == optionId) {
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-danger');
                    }
                });
                
                // Show feedback
                if (data.is_correct) {
                    showNotification('✓ Correct! +' + data.points_earned + ' points', 'success');
                } else {
                    showNotification('✗ Incorrect!', 'danger');
                }
                
                // Move to next question after delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        });
    }

    function showNotification(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 2000);
    }

    // Add click handlers to options
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!answerSubmitted) {
                submitAnswer(this.dataset.optionId);
            }
        });
    });

    // Anti-cheat: detect tab switching
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && !answerSubmitted) {
            fetch('/api/v1/anti-cheat/tab-switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    action: 'blur'
                })
            });
            showNotification('Warning: Tab switching detected!', 'warning');
        }
    });

    // Prevent right click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // Start timer
    startTimer();
</script>
@endpush
@endsection