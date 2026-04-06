@extends('layouts.app')

@section('title', 'Quiz Session - ' . $quiz->title)

@section('content')
<style>
    .option-btn {
        transition: all 0.3s;
        text-align: left;
        padding: 15px;
        margin-bottom: 10px;
        width: 100%;
        white-space: normal;
        word-wrap: break-word;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        background: white;
        cursor: pointer;
    }
    .option-btn:hover:not(:disabled) {
        transform: translateX(5px);
        background-color: #f8f9fa;
        border-color: #007bff;
    }
    .option-selected {
        background-color: #007bff !important;
        color: white !important;
        border-color: #007bff !important;
    }
    .option-correct {
        background-color: #28a745 !important;
        color: white !important;
        border-color: #28a745 !important;
    }
    .option-incorrect {
        background-color: #dc3545 !important;
        color: white !important;
        border-color: #dc3545 !important;
    }
    .timer {
        font-size: 1.2rem;
        font-weight: bold;
        font-family: monospace;
        background: #0e0e0f;
        padding: 5px 12px;
        border-radius: 25px;
        display: inline-block;
    }
    .timer-danger {
        background-color: #dc3545 !important;
        color: white !important;
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    .feedback-message {
        padding: 12px;
        border-radius: 8px;
        margin-top: 15px;
        animation: fadeIn 0.3s ease;
    }
    .feedback-correct {
        background-color: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }
    .feedback-incorrect {
        background-color: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .next-question-loader {
        text-align: center;
        margin-top: 20px;
        padding: 10px;
        display: none;
    }
    .next-question-loader .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        display: none;
    }
    .answer-waiting {
        background-color: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 10px;
        margin-top: 15px;
        text-align: center;
        animation: pulse 2s infinite;
    }
    .quiz-timer {
        font-family: monospace;
        font-weight: bold;
    }
    .stat-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 10px;
    }
</style>

<div class="row mb-3">
    <div class="col-md-8">
        <h3><i class="fas fa-question-circle"></i> {{ $quiz->title }}</h3>
        <p class="text-muted">Question <strong id="currentQuestionNum">{{ $currentQuestionNumber }}</strong> of <strong>{{ $totalQuestions }}</strong></p>
    </div>
    <div class="col-md-4 text-end">
        <span class="badge bg-primary fs-5 p-2 me-2">
            <i class="fas fa-star"></i> Score: <span id="score">{{ $attempt->score }}</span>
        </span>
        <span class="badge bg-info fs-5 p-2">
            <i class="fas fa-clock"></i> <span id="timer" class="timer">00:00</span>
        </span>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0" id="questionText">{{ $currentQuestion->question_text }}</h5>
            </div>
            <div class="card-body">
                @if($currentQuestion->question_type == 'multiple_choice')
                    <div class="alert alert-info">
                        <i class="fas fa-check-double"></i> 
                        <strong>Multiple Select Question</strong> - Select ALL correct answers. Your answers will be submitted automatically when the timer ends.
                        <div class="mt-2">
                            <span class="badge bg-primary">Selected: <span id="selectedCount">0</span></span>
                            <span class="badge bg-secondary">Correct answers: {{ $currentQuestion->options->where('is_correct', true)->count() }}</span>
                        </div>
                    </div>
                @endif

                <div id="optionsContainer">
                    @foreach($currentQuestion->options as $index => $option)
                        <button class="option-btn" 
                                data-option-id="{{ $option->id }}"
                                data-option-index="{{ $index }}"
                                data-is-correct="{{ $option->is_correct ? 'true' : 'false' }}"
                                data-question-type="{{ $currentQuestion->question_type }}">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-3" style="font-size: 1rem; min-width: 35px;">
                                    {{ chr(65 + $index) }}
                                </span>
                                <span class="flex-grow-1">{{ $option->option_text }}</span>
                                @if($currentQuestion->question_type == 'multiple_choice')
                                    <i class="fas fa-check-circle selection-check" style="opacity: 0; font-size: 1.2rem;"></i>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="answer-waiting" id="answerWaitingMessage">
                    <i class="fas fa-hourglass-half"></i> 
                    <span>Your answer will be submitted automatically when the timer ends.</span>
                </div>

                <div id="feedback" class="feedback-message" style="display: none;"></div>
                <div class="next-question-loader" id="nextQuestionLoader">
                    <div class="spinner"></div>
                    <p class="mt-2">Loading next question...</p>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span>Quiz Progress</span>
                    <span id="progressText">{{ $answeredCount }}/{{ $totalQuestions }} answered</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" id="progressBar" 
                         style="width: {{ ($answeredCount / $totalQuestions) * 100 }}%">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Your Stats</h5>
            </div>
            <div class="card-body">
                <div class="stat-box">
                    <div class="d-flex justify-content-between">
                        <span>Correct Answers:</span>
                        <strong id="correctCount" class="text-success">{{ $attempt->correct_answers }}</strong>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="d-flex justify-content-between">
                        <span>Incorrect Answers:</span>
                        <strong id="incorrectCount" class="text-danger">{{ $attempt->incorrect_answers }}</strong>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="d-flex justify-content-between">
                        <span>Points Earned:</span>
                        <strong id="pointsDisplay" class="text-primary">{{ $attempt->score }}</strong>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>This Question:</span>
                    <strong>{{ $currentQuestion->points }} pts</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Time Limit:</span>
                    <strong>{{ $remainingTimeSeconds }}s</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="answerForm" style="display: none;">
    @csrf
    <input type="hidden" name="question_id" id="questionId">
    <input type="hidden" name="option_id" id="optionId">
    <input type="hidden" name="selected_options" id="selectedOptions">
    <input type="hidden" name="time_taken" id="timeTaken">
    <input type="hidden" name="question_type" id="questionType">
</form>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

@push('scripts')
<script>
    const quizId = {{ $quiz->id }};
    const attemptId = {{ $attempt->id }};
    const questionId = {{ $currentQuestion->id }};
    const questionType = '{{ $currentQuestion->question_type }}';
    const timeSeconds = {{ (int) $remainingTimeSeconds }};
    const questionDuration = {{ (int) $currentQuestion->time_seconds }};
    const showAnswer = {{ $currentQuestion->show_answer ? 'true' : 'false' }};
    const isMultipleChoice = questionType === 'multiple_choice';
    
    let timeLeft = parseInt(timeSeconds, 10);
    let timerInterval;
    let answerSubmitted = false;
    let startTime = Date.now();
    let isInternalNavigation = false;
    let selectedOptions = [];
    let heartbeatInterval;
    let selectedOptionId = null;

    // Set form values
    function setFormValues() {
        document.getElementById('questionId').value = questionId;
        document.getElementById('questionType').value = questionType;
        
        if (isMultipleChoice) {
            document.getElementById('selectedOptions').value = JSON.stringify(selectedOptions);
        } else {
            document.getElementById('optionId').value = selectedOptionId || '';
        }
    }

    // Submit answer when timer ends
    function submitAnswerOnTimerEnd() {
        if (answerSubmitted) return;
        
        const elapsedSincePageLoad = Math.floor((Date.now() - startTime) / 1000);
        const timeTaken = Math.min((parseInt(questionDuration, 10) - parseInt(timeSeconds, 10)) + elapsedSincePageLoad, parseInt(questionDuration, 10));
        document.getElementById('timeTaken').value = timeTaken;
        
        setFormValues();
        
        const formData = new FormData(document.getElementById('answerForm'));
        const url = isMultipleChoice 
            ? `/user/quiz/attempt/${quizId}/${attemptId}/submit-multiple`
            : `/user/quiz/attempt/${quizId}/${attemptId}/submit`;
        
        showLoading();
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                answerSubmitted = true;
                clearInterval(timerInterval);
                updateUI(data);
            } else if (data.redirect_url) {
                // Attempt already completed, redirect to results
                isInternalNavigation = true;
                window.location.href = data.redirect_url;
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                hideLoading();
            }
        })
        .catch(err => {
            console.error('Submit error:', err);
            alert('Network error. Please refresh the page and try again.');
            hideLoading();
        });
    }

    // Timer functions
    function startTimer() {
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            if (!answerSubmitted) {
                timeLeft = Math.max(0, parseInt(timeLeft, 10) - 1);
                updateTimerDisplay();
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    submitAnswerOnTimerEnd();
                }
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const safeTimeLeft = Math.max(0, parseInt(timeLeft, 10) || 0);
        const minutes = Math.floor(safeTimeLeft / 60);
        const seconds = safeTimeLeft % 60;
        const timerEl = document.getElementById('timer');
        if (timerEl) {
            timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (safeTimeLeft < 10 && safeTimeLeft > 0) {
                timerEl.classList.add('timer-danger');
            } else {
                timerEl.classList.remove('timer-danger');
            }
        }
    }

    function showFeedback(isCorrect, pointsEarned, explanation, correctAnswerText) {
        const feedbackDiv = document.getElementById('feedback');
        if (!feedbackDiv) return;
        feedbackDiv.style.display = 'block';

        if (isCorrect) {
            feedbackDiv.className = 'feedback-message feedback-correct';
            feedbackDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                    <div>
                        <strong class="text-success">Correct!</strong>
                        <p class="mb-0">You earned ${pointsEarned} points.</p>
                        ${explanation ? `<small class="text-muted">${explanation}</small>` : ''}
                    </div>
                </div>
            `;
        } else {
            let correctAnswerHtml = '';
            if (showAnswer && correctAnswerText) {
                correctAnswerHtml = `<div class="mt-2 p-2 bg-light rounded"><i class="fas fa-lightbulb text-warning"></i> <strong>Correct answer:</strong> ${correctAnswerText}</div>`;
            }
            feedbackDiv.className = 'feedback-message feedback-incorrect';
            feedbackDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-times-circle text-danger fa-2x me-3"></i>
                    <div>
                        <strong class="text-danger">Incorrect!</strong>
                        <p class="mb-0">You earned 0 points.</p>
                        ${correctAnswerHtml}
                        ${explanation ? `<small class="text-muted mt-2">${explanation}</small>` : ''}
                    </div>
                </div>
            `;
        }
        
        setTimeout(() => {
            feedbackDiv.style.display = 'none';
        }, 2000);
    }

    function showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'flex';
    }
    
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'none';
    }
    
    function showNextQuestionLoader() {
        const loader = document.getElementById('nextQuestionLoader');
        if (loader) loader.style.display = 'block';
        const waitingMsg = document.getElementById('answerWaitingMessage');
        if (waitingMsg) waitingMsg.style.display = 'none';
    }

    function loadNextQuestion() {
        showNextQuestionLoader();
        setTimeout(() => {
            isInternalNavigation = true;
            window.location.reload();
        }, 1500);
    }

    function updateUI(data) {
        // Update scores
        const scoreEl = document.getElementById('score');
        const pointsDisplayEl = document.getElementById('pointsDisplay');
        const correctCountEl = document.getElementById('correctCount');
        const incorrectCountEl = document.getElementById('incorrectCount');
        const progressTextEl = document.getElementById('progressText');
        const progressBarEl = document.getElementById('progressBar');
        
        if (scoreEl) scoreEl.textContent = data.current_score;
        if (pointsDisplayEl) pointsDisplayEl.textContent = data.current_score;
        if (correctCountEl) correctCountEl.textContent = data.correct_answers;
        if (incorrectCountEl) incorrectCountEl.textContent = data.incorrect_answers;
        if (progressTextEl) progressTextEl.textContent = `${data.answered_count}/${data.total_questions} answered`;
        if (progressBarEl) progressBarEl.style.width = `${(data.answered_count / data.total_questions) * 100}%`;
        
        // Disable all option buttons
        const allButtons = document.querySelectorAll('.option-btn');
        allButtons.forEach(btn => btn.disabled = true);
        
        // Hide waiting message
        const waitingMsg = document.getElementById('answerWaitingMessage');
        if (waitingMsg) waitingMsg.style.display = 'none';
        
        // Show correct/incorrect styling
        if (isMultipleChoice) {
            allButtons.forEach(btn => {
                const isCorrectOption = btn.dataset.isCorrect === 'true';
                if (isCorrectOption) {
                    btn.classList.add('option-correct');
                } else if (selectedOptions.includes(parseInt(btn.dataset.optionId))) {
                    btn.classList.add('option-incorrect');
                }
            });
            
            let correctAnswerText = '';
            if (showAnswer) {
                allButtons.forEach(btn => {
                    if (btn.dataset.isCorrect === 'true') {
                        const answerSpan = btn.querySelector('span:last-child');
                        if (answerSpan) correctAnswerText += (correctAnswerText ? ', ' : '') + answerSpan.innerText;
                    }
                });
            }
            
            showFeedback(data.is_correct, data.points_earned, '', correctAnswerText);
        } else {
            allButtons.forEach(btn => {
                if (data.is_correct && btn.dataset.optionId == data.selected_option_id) {
                    btn.classList.add('option-correct');
                } else if (!data.is_correct && btn.dataset.optionId == data.selected_option_id) {
                    btn.classList.add('option-incorrect');
                }
            });
            
            let correctAnswerText = '';
            if (showAnswer && !data.is_correct && data.correct_option_id) {
                allButtons.forEach(btn => {
                    if (btn.dataset.optionId == data.correct_option_id) {
                        btn.classList.add('option-correct');
                        const answerSpan = btn.querySelector('span:last-child');
                        if (answerSpan) correctAnswerText = answerSpan.innerText;
                    }
                });
            }
            
            showFeedback(data.is_correct, data.points_earned, '', correctAnswerText);
        }
        
        hideLoading();
        
        if (data.is_completed) {
            setTimeout(() => {
                isInternalNavigation = true;
                window.location.href = `/user/quiz/result/${quizId}/${attemptId}`;
            }, 2000);
        } else {
            loadNextQuestion();
        }
    }

    // Event listeners - save selections without submitting
    if (isMultipleChoice) {
        const selectedCountSpan = document.getElementById('selectedCount');
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (answerSubmitted) return;
                const optionId = parseInt(this.dataset.optionId);
                const index = selectedOptions.indexOf(optionId);
                const checkIcon = this.querySelector('.selection-check');
                if (index === -1) {
                    selectedOptions.push(optionId);
                    this.classList.add('option-selected');
                    if (checkIcon) checkIcon.style.opacity = '1';
                } else {
                    selectedOptions.splice(index, 1);
                    this.classList.remove('option-selected');
                    if (checkIcon) checkIcon.style.opacity = '0';
                }
                if (selectedCountSpan) selectedCountSpan.textContent = selectedOptions.length;
            });
        });
    } else {
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (answerSubmitted) return;
                // Remove selected class from all buttons
                document.querySelectorAll('.option-btn').forEach(b => {
                    b.classList.remove('option-selected');
                });
                selectedOptionId = this.dataset.optionId;
                this.classList.add('option-selected');
            });
        });
    }

    // Heartbeat to keep user active
    function sendHeartbeat() {
        if (answerSubmitted) return;
        
        fetch(`/user/quiz/attempt/${quizId}/${attemptId}/heartbeat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({})
        }).catch(err => console.error('Heartbeat error:', err));
    }

    heartbeatInterval = setInterval(sendHeartbeat, 15000);

    // Handle page leave
    window.addEventListener('beforeunload', function() {
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        if (timerInterval) clearInterval(timerInterval);

        if (answerSubmitted || isInternalNavigation) {
            return;
        }
        
        fetch(`/user/quiz/attempt/${quizId}/${attemptId}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            keepalive: true,
            body: JSON.stringify({})
        }).catch(() => {});
    });

    // Anti-cheat
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && !answerSubmitted) {
            fetch('/api/v1/anti-cheat/tab-switch', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ attempt_id: attemptId, action: 'blur' })
            }).catch(() => {});
        }
    });

    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // Start timer
    startTimer();
    
    console.log('Quiz session initialized - Auto-submit on timer end', {
        quizId, attemptId, questionId, timeSeconds, isMultipleChoice
    });
</script>
@endpush
@endsection
