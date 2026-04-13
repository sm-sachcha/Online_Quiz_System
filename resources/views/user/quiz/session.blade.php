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
                <div id="questionInstructions">
                @if($currentQuestion->question_type == 'multiple_choice')
                    <div class="alert alert-info" id="multipleChoiceAlert">
                        <i class="fas fa-check-double"></i> 
                        <strong>Multiple Select Question</strong> - Select ALL correct answers. Your answers will be submitted automatically when the timer ends.
                        <div class="mt-2">
                            <span class="badge bg-primary">Selected: <span id="selectedCount">0</span></span>
                            <span class="badge bg-secondary">Correct answers: {{ $currentQuestion->options->where('is_correct', true)->count() }}</span>
                        </div>
                    </div>
                @endif
                </div>

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
                    <strong id="questionPoints">{{ $currentQuestion->points }} pts</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Time Limit:</span>
                    <strong id="questionTimeLimit">{{ $currentQuestion->time_seconds }}s</strong>
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
    const preQuestionCountdownSeconds = 5;
    const totalQuestions = {{ (int) $totalQuestions }};
    let currentQuestionId = {{ $currentQuestion->id }};
    let currentQuestionType = @json($currentQuestion->question_type);
    let currentQuestionDuration = {{ (int) $currentQuestion->time_seconds }};
    let currentQuestionNumber = {{ (int) $currentQuestionNumber }};
    let questionStartSeconds = {{ (int) $remainingTimeSeconds }};
    let currentShowAnswer = {{ $currentQuestion->show_answer ? 'true' : 'false' }};
    let timeLeft = parseInt(questionStartSeconds, 10);
    let timerInterval;
    let answerSubmitted = false;
    let startTime = null;
    let isInternalNavigation = false;
    let selectedOptions = [];
    let selectedOptionId = null;
    let waitingForNextQuestion = false;
    let attemptHeartbeatInterval = null;
    let leaveSignalSent = false;
    const csrfToken = '{{ csrf_token() }}';

    function isMultipleChoiceQuestion() {
        return currentQuestionType === 'multiple_choice';
    }

    function setFormValues() {
        document.getElementById('questionId').value = currentQuestionId;
        document.getElementById('questionType').value = currentQuestionType;
        document.getElementById('selectedOptions').value = '';
        document.getElementById('optionId').value = '';
        
        if (isMultipleChoiceQuestion()) {
            document.getElementById('selectedOptions').value = JSON.stringify(selectedOptions);
        } else {
            document.getElementById('optionId').value = selectedOptionId || '';
        }
    }

    function submitAnswerOnTimerEnd() {
        if (answerSubmitted) return;
        
        const elapsedSincePageLoad = Math.floor((Date.now() - startTime) / 1000);
        const timeTaken = Math.min((parseInt(currentQuestionDuration, 10) - parseInt(questionStartSeconds, 10)) + elapsedSincePageLoad, parseInt(currentQuestionDuration, 10));
        document.getElementById('timeTaken').value = timeTaken;
        
        setFormValues();
        
        const formData = new FormData(document.getElementById('answerForm'));
        const url = isMultipleChoiceQuestion() 
            ? `/user/quiz/attempt/${quizId}/${attemptId}/submit-multiple`
            : `/user/quiz/attempt/${quizId}/${attemptId}/submit`;
        
        showLoading();
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
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

    function startAttemptHeartbeat() {
        if (attemptHeartbeatInterval) {
            return;
        }

        attemptHeartbeatInterval = setInterval(() => {
            if (isInternalNavigation) {
                return;
            }

            fetch(`/user/quiz/attempt/${quizId}/${attemptId}/heartbeat`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).catch(() => {});
        }, 15000);
    }

    function stopAttemptHeartbeat() {
        if (!attemptHeartbeatInterval) {
            return;
        }

        clearInterval(attemptHeartbeatInterval);
        attemptHeartbeatInterval = null;
    }

    function notifyAttemptLeave() {
        if (isInternalNavigation || leaveSignalSent) {
            return;
        }

        leaveSignalSent = true;
        stopAttemptHeartbeat();

        const leaveData = new FormData();
        leaveData.append('_token', csrfToken);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(`/user/quiz/attempt/${quizId}/${attemptId}/leave`, leaveData);
            return;
        }

        fetch(`/user/quiz/attempt/${quizId}/${attemptId}/leave`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            keepalive: true
        }).catch(() => {});
    }

    // Timer functions
    function startTimer() {
        startTime = Date.now();
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            if (!answerSubmitted && !waitingForNextQuestion) {
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

    function getOptionTextFromButton(btn) {
        const textNode = btn.querySelector('.option-text');
        return textNode ? textNode.innerText : '';
    }

    function buildOptionsMarkup(options, questionType) {
        return options.map((option, index) => `
            <button class="option-btn"
                    data-option-id="${option.id}"
                    data-option-index="${index}"
                    data-is-correct="${option.is_correct ? 'true' : 'false'}"
                    data-question-type="${questionType}">
                <div class="d-flex align-items-center">
                    <span class="badge bg-secondary me-3" style="font-size: 1rem; min-width: 35px;">
                        ${String.fromCharCode(65 + index)}
                    </span>
                    <span class="flex-grow-1 option-text">${option.text}</span>
                    ${questionType === 'multiple_choice' ? '<i class="fas fa-check-circle selection-check" style="opacity: 0; font-size: 1.2rem;"></i>' : ''}
                </div>
            </button>
        `).join('');
    }

    function bindOptionHandlers() {
        const selectedCountSpan = document.getElementById('selectedCount');

        if (isMultipleChoiceQuestion()) {
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (answerSubmitted || waitingForNextQuestion) return;

                    const optionId = parseInt(this.dataset.optionId, 10);
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

            return;
        }

        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (answerSubmitted || waitingForNextQuestion) return;

                document.querySelectorAll('.option-btn').forEach(button => {
                    button.classList.remove('option-selected');
                });

                selectedOptionId = this.dataset.optionId;
                this.classList.add('option-selected');
            });
        });
    }

    function renderQuestion(question) {
        currentQuestionId = Number(question.question_id);
        currentQuestionType = question.question_type;
        currentQuestionDuration = Number(question.time_seconds);
        currentQuestionNumber = Number(question.question_number);
        currentShowAnswer = Boolean(question.show_answer);
        questionStartSeconds = currentQuestionDuration;
        timeLeft = currentQuestionDuration;
        answerSubmitted = false;
        waitingForNextQuestion = false;
        startTime = null;
        selectedOptions = [];
        selectedOptionId = null;

        document.getElementById('questionText').textContent = question.question_text;
        document.getElementById('currentQuestionNum').textContent = currentQuestionNumber;
        document.getElementById('questionPoints').textContent = `${question.points} pts`;
        document.getElementById('questionTimeLimit').textContent = `${question.time_seconds}s`;
        document.getElementById('optionsContainer').innerHTML = buildOptionsMarkup(question.options || [], question.question_type);

        const instructions = document.getElementById('questionInstructions');
        if (instructions) {
            if (question.question_type === 'multiple_choice') {
                const correctCount = (question.options || []).filter(option => option.is_correct).length;
                instructions.innerHTML = `
                    <div class="alert alert-info" id="multipleChoiceAlert">
                        <i class="fas fa-check-double"></i>
                        <strong>Multiple Select Question</strong> - Select ALL correct answers. Your answers will be submitted automatically when the timer ends.
                        <div class="mt-2">
                            <span class="badge bg-primary">Selected: <span id="selectedCount">0</span></span>
                            <span class="badge bg-secondary">Correct answers: ${correctCount}</span>
                        </div>
                    </div>
                `;
            } else {
                instructions.innerHTML = '';
            }
        }

        const feedback = document.getElementById('feedback');
        if (feedback) {
            feedback.style.display = 'none';
            feedback.innerHTML = '';
        }

        const waitingMsg = document.getElementById('answerWaitingMessage');
        if (waitingMsg) waitingMsg.style.display = 'block';

        const loader = document.getElementById('nextQuestionLoader');
        if (loader) loader.style.display = 'none';

        if (timerInterval) clearInterval(timerInterval);
        updateTimerDisplay();
        bindOptionHandlers();
        setFormValues();
        hideLoading();
        showQuestionStartCountdown(preQuestionCountdownSeconds, startTimer);
    }

    function showQuestionStartCountdown(seconds, onComplete) {
        let countdown = seconds;

        const overlay = document.createElement('div');
        overlay.className = 'quiz-start-overlay';
        overlay.innerHTML = `
            <div class="countdown-box">
                <div class="countdown-number" id="questionStartCountdownNumber">${countdown}</div>
                <div class="countdown-label">Question Starting</div>
                <div class="countdown-sub">Get ready to answer...</div>
            </div>
        `;

        document.body.appendChild(overlay);

        const interval = setInterval(() => {
            countdown--;
            const countEl = document.getElementById('questionStartCountdownNumber');
            if (countEl) {
                countEl.textContent = Math.max(countdown, 0);
            }

            if (countdown <= 0) {
                clearInterval(interval);
                overlay.remove();
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        }, 1000);
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
            if (currentShowAnswer && correctAnswerText) {
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
        waitingForNextQuestion = true;
    }

    function updateUI(data) {
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
        
        if (isMultipleChoiceQuestion()) {
            allButtons.forEach(btn => {
                const isCorrectOption = btn.dataset.isCorrect === 'true';
                if (isCorrectOption) {
                    btn.classList.add('option-correct');
                } else if (selectedOptions.includes(parseInt(btn.dataset.optionId))) {
                    btn.classList.add('option-incorrect');
                }
            });
            
            let correctAnswerText = '';
            if (currentShowAnswer) {
                allButtons.forEach(btn => {
                    if (btn.dataset.isCorrect === 'true') {
                        const answerText = getOptionTextFromButton(btn);
                        if (answerText) correctAnswerText += (correctAnswerText ? ', ' : '') + answerText;
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
            if (currentShowAnswer && !data.is_correct && data.correct_option_id) {
                allButtons.forEach(btn => {
                    if (btn.dataset.optionId == data.correct_option_id) {
                        btn.classList.add('option-correct');
                        correctAnswerText = getOptionTextFromButton(btn);
                    }
                });
            }
            
            showFeedback(data.is_correct, data.points_earned, '', correctAnswerText);
        }
        
        hideLoading();
        
        if (data.is_completed) {
            showNextQuestionLoader();
            if (data.redirect_url) {
                setTimeout(() => {
                    stopAttemptHeartbeat();
                    isInternalNavigation = true;
                    window.location.href = data.redirect_url;
                }, 1200);
            }
        } else {
            showNextQuestionLoader();
            if (data.next_question) {
                setTimeout(() => {
                    renderQuestion(data.next_question);
                }, 1200);
            }
        }
    }

    bindOptionHandlers();
    startAttemptHeartbeat();

    // Handle page leave
    window.addEventListener('beforeunload', function() {
        if (timerInterval) clearInterval(timerInterval);
        notifyAttemptLeave();
    });

    window.addEventListener('pagehide', function() {
        notifyAttemptLeave();
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

    if (typeof window.initializeEcho === 'function') {
        window.initializeEcho(quizId, {
            onQuizEnded() {
                if (answerSubmitted) {
                    return;
                }

                answerSubmitted = true;

                if (timerInterval) clearInterval(timerInterval);

                showNotification('The quiz was ended by the admin.', 'warning');

                setTimeout(() => {
                    stopAttemptHeartbeat();
                    isInternalNavigation = true;
                    window.location.href = `/user/quiz/result/${quizId}/${attemptId}`;
                }, 1200);
            }
        });
    }

    if (typeof window.initializeAttemptEcho === 'function') {
        window.initializeAttemptEcho(attemptId, {
            onAttemptQuestionBroadcasted(event) {
                if (!event || Number(event.question_id) === Number(currentQuestionId)) {
                    return;
                }

                renderQuestion(event);
            },
            onCurrentQuestionBroadcasted(event) {
                // Handle synchronized quiz broadcast
                if (!event) {
                    return;
                }

                // If quiz ended (no question_id), show completion message
                if (!event.question_id) {
                    showNotification('Quiz finished by admin!', 'info');
                    stopAttemptHeartbeat();
                    setTimeout(() => {
                        window.location.href = `/user/quiz/result/${quizId}/${attemptId}`;
                    }, 2000);
                    return;
                }

                // If it's a different question, render it
                if (Number(event.question_id) !== Number(currentQuestionId)) {
                    renderQuestion(event);
                }
            },
            onAttemptResultUpdated(event) {
                const payload = event.payload || {};

                if (!payload.redirect_url) {
                    return;
                }

                showNotification(
                    payload.passed ? 'Quiz completed! Preparing your result...' : 'Quiz finished. Preparing your result...',
                    payload.passed ? 'success' : 'warning'
                );

                setTimeout(() => {
                    stopAttemptHeartbeat();
                    isInternalNavigation = true;
                    window.location.href = payload.redirect_url;
                }, 1200);
            }
        });
    }

    timeLeft = parseInt(questionStartSeconds, 10);
    updateTimerDisplay();
    showQuestionStartCountdown(preQuestionCountdownSeconds, startTimer);
    
    console.log('Quiz session initialized - Auto-submit on timer end', {
        quizId, attemptId, currentQuestionId, questionStartSeconds, currentQuestionType
    });
</script>
@endpush
@endsection
