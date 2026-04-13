@if($quiz->is_synchronized && $isQuizStarted)
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-sync-alt"></i> Synchronized Quiz Control
        </h5>
        <span class="badge bg-light text-primary">Question {{ $quiz->current_question_number ?? 0 }} of {{ $quiz->questions()->count() }}</span>
    </div>
    <div class="card-body">
        @if($quiz->currentQuestion)
            <div class="row mb-3">
                <div class="col-md-8">
                    <h6 class="text-muted">Current Question:</h6>
                    <div class="alert alert-info mb-0">
                        <strong>Q{{ $quiz->current_question_number }}:</strong> {{ Str::limit($quiz->currentQuestion->question_text, 100) }}
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Time per Question:</h6>
                    <div class="text-center">
                        <strong class="h5">{{ $quiz->currentQuestion->time_seconds ?? 30 }}s</strong>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning mb-3">
                <i class="fas fa-info-circle"></i> No current question set. Select a question below to start.
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <label class="form-label"><strong>Navigate Questions:</strong></label>
                <div class="btn-group d-flex gap-2" role="group">
                    <button type="button" class="btn btn-outline-secondary" id="prevQuestBtn" 
                            {{ !$quiz->current_question_number || $quiz->current_question_number <= 1 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    
                    <div class="flex-grow-1">
                        <select class="form-select" id="questionSelect" aria-label="Select question">
                            <option value="">-- Select a Question --</option>
                            @forelse($quiz->questions()->orderBy('order')->get() as $index => $question)
                                <option value="{{ $question->id }}" {{ $quiz->current_question_id == $question->id ? 'selected' : '' }}>
                                    Q{{ $index + 1 }}: {{ Str::limit($question->question_text, 50) }}
                                </option>
                            @empty
                                <option value="" disabled>No questions available</option>
                            @endforelse
                        </select>
                    </div>

                    <button type="button" class="btn btn-outline-secondary" id="nextQuestBtn"
                            {{ !$quiz->current_question_number || $quiz->current_question_number >= $quiz->questions()->count() ? 'disabled' : '' }}>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <button type="button" class="btn btn-success" id="endQuizBtn">
                    <i class="fas fa-check"></i> End Quiz
                </button>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-redo"></i> Changes are broadcast to all participants instantly.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quizId = {{ $quiz->id }};
    const questionSelect = document.getElementById('questionSelect');
    const prevBtn = document.getElementById('prevQuestBtn');
    const nextBtn = document.getElementById('nextQuestBtn');
    const endBtn = document.getElementById('endQuizBtn');
    const totalQuestions = {{ $quiz->questions()->count() }};

    function updateButtonStates() {
        const currentValue = questionSelect.value;
        const selectedIndex = Array.from(questionSelect.options).findIndex(opt => opt.value === currentValue);
        
        prevBtn.disabled = selectedIndex <= 1;
        nextBtn.disabled = selectedIndex >= totalQuestions || selectedIndex === 0;
    }

    questionSelect.addEventListener('change', function() {
        if (!this.value) return;

        const selectedIndex = Array.from(this.options).findIndex(opt => opt.value === this.value && opt.value);
        const totalQuestions = totalQuestions;

        fetch(`/admin/quiz-broadcast/${quizId}/set-current-question`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_id: this.value,
                question_number: selectedIndex,
                total_questions: totalQuestions
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Question broadcasted to all participants!', 'success');
                updateButtonStates();
                // Reload to update the UI
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.error || 'Failed to set question', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error setting question', 'danger');
        });
    });

    prevBtn.addEventListener('click', function() {
        fetch(`/admin/quiz-broadcast/${quizId}/previous-question`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Going to previous question...', 'info');
                setTimeout(() => location.reload(), 500);
            }
        });
    });

    nextBtn.addEventListener('click', function() {
        fetch(`/admin/quiz-broadcast/${quizId}/next-question`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.message === 'Quiz finished') {
                    showNotification('Quiz finished! All participants redirected.', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Moving to next question...', 'info');
                    setTimeout(() => location.reload(), 500);
                }
            }
        });
    });

    endBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to end the quiz for all participants?')) {
            fetch(`/admin/quiz-broadcast/${quizId}/next-question`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Quiz ended for all participants!', 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            });
        }
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    updateButtonStates();
});
</script>
@endif
