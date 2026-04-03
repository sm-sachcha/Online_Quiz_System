@extends('layouts.admin')

@section('title', 'Edit Question')

@section('content')
<style>
    .correct-option {
        border-left: 4px solid #28a745 !important;
        background-color: #e8f5e9 !important;
    }
    .option-card {
        transition: all 0.3s ease;
        margin-bottom: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    .btn-set-correct {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-correct-selected {
        background-color: #28a745 !important;
    }
    .remove-option-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        width: 32px;
        height: 32px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .remove-option-btn:hover {
        background-color: #c82333;
        transform: scale(1.05);
    }
    .btn-set-correct:hover {
        transform: scale(1.05);
    }
    .question-type-badge {
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: bold;
    }
    .badge-multiple {
        background-color: #17a2b8;
        color: white;
    }
    .badge-single {
        background-color: #ffc107;
        color: #856404;
    }
    .badge-truefalse {
        background-color: #6c757d;
        color: white;
    }
</style>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Question</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quizzes.questions.update', [$quiz, $question]) }}" method="POST" id="questionForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('question_text') is-invalid @enderror" 
                                  id="question_text" name="question_text" rows="3" required>{{ old('question_text', $question->question_text) }}</textarea>
                        @error('question_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Question Type</label>
                            <div>
                                @php
                                    $typeClass = '';
                                    $typeLabel = '';
                                    if($question->question_type == 'multiple_choice') {
                                        $typeClass = 'badge-multiple';
                                        $typeLabel = 'Multiple Choice (Select all that apply)';
                                    } elseif($question->question_type == 'single_choice') {
                                        $typeClass = 'badge-single';
                                        $typeLabel = 'Single Choice (Select only one)';
                                    } else {
                                        $typeClass = 'badge-truefalse';
                                        $typeLabel = 'True / False';
                                    }
                                @endphp
                                <span class="question-type-badge {{ $typeClass }}">
                                    <i class="fas {{ $question->question_type == 'multiple_choice' ? 'fa-check-double' : ($question->question_type == 'single_choice' ? 'fa-dot-circle' : 'fa-adjust') }}"></i>
                                    {{ $typeLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="points" class="form-label">Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('points') is-invalid @enderror" 
                                   id="points" name="points" value="{{ old('points', $question->points) }}" min="1" max="100" required>
                            @error('points')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="time_seconds" class="form-label">Time (seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('time_seconds') is-invalid @enderror" 
                                   id="time_seconds" name="time_seconds" value="{{ old('time_seconds', $question->time_seconds) }}" min="10" max="300" required>
                            @error('time_seconds')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Answer Options</label>
                        <div class="alert alert-info mb-2 small">
                            @if($question->question_type == 'multiple_choice')
                                <i class="fas fa-info-circle"></i> <strong>Multiple Choice:</strong> You can select MULTIPLE correct answers by clicking "Set Correct" on all correct options.
                            @elseif($question->question_type == 'single_choice')
                                <i class="fas fa-info-circle"></i> <strong>Single Choice:</strong> Only ONE option can be marked as correct. Selecting a new correct option will automatically unselect the previous one.
                            @else
                                <i class="fas fa-info-circle"></i> <strong>True/False:</strong> Only ONE option (True or False) can be marked as correct.
                            @endif
                        </div>
                        
                        <div id="options-list">
                            @foreach($question->options as $index => $option)
                                <div class="option-card card mb-2 {{ $option->is_correct ? 'correct-option' : '' }}" data-option-id="{{ $option->id }}">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="badge bg-secondary">{{ chr(65 + $index) }}</span>
                                            </div>
                                            <div class="col">
                                                <input type="text" class="form-control" 
                                                       name="options[{{ $index }}][text]" 
                                                       value="{{ $option->option_text }}" required>
                                            </div>
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-sm set-correct-btn {{ $option->is_correct ? 'btn-correct-selected' : 'btn-secondary' }}" 
                                                        data-option-index="{{ $index }}"
                                                        data-question-type="{{ $question->question_type }}">
                                                    {{ $option->is_correct ? '✓ Correct' : 'Set Correct' }}
                                                </button>
                                            </div>
                                            <div class="col-auto">
                                                <button type="button" class="remove-option-btn" title="Remove option">✕</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option->id }}">
                                        <input type="hidden" name="options[{{ $index }}][is_correct]" value="{{ $option->is_correct ? '1' : '0' }}" class="correct-value">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-success mt-2" id="add-option">
                            <i class="fas fa-plus"></i> Add Option
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="explanation" class="form-label">Explanation (Optional)</label>
                        <textarea class="form-control @error('explanation') is-invalid @enderror" 
                                  id="explanation" name="explanation" rows="2">{{ old('explanation', $question->explanation) }}</textarea>
                        @error('explanation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Show Answer Option -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show_answer" name="show_answer" value="1" 
                                   {{ old('show_answer', $question->show_answer) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_answer">
                                <i class="fas fa-eye"></i> Show correct answer to users after they answer
                            </label>
                            <div class="form-text text-muted">
                                If enabled, users will see the correct answer after they submit their response.
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Note:</strong> Changing question options may affect existing answers.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Question
                        </button>
                        <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let optionCounter = {{ $question->options->count() }};
    const questionType = '{{ $question->question_type }}';
    const isMultipleChoice = questionType === 'multiple_choice';
    const isSingleChoice = questionType === 'single_choice';
    const isTrueFalse = questionType === 'true_false';
    
    function addOption(optionText = '', isCorrect = false) {
        // For True/False, don't allow adding more options
        if (isTrueFalse && document.querySelectorAll('.option-card').length >= 2) {
            alert('True/False questions can only have 2 options (True and False)');
            return;
        }
        
        optionCounter++;
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-card card mb-2';
        optionDiv.innerHTML = `
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="badge bg-secondary">${String.fromCharCode(64 + optionCounter)}</span>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" 
                               name="options[${optionCounter}][text]" 
                               value="${escapeHtml(optionText)}" 
                               placeholder="Enter option text" required>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-secondary set-correct-btn" 
                                data-option-index="${optionCounter}"
                                data-question-type="${questionType}">
                            Set Correct
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="remove-option-btn" title="Remove option">✕</button>
                    </div>
                </div>
                <input type="hidden" name="options[${optionCounter}][is_correct]" value="${isCorrect ? '1' : '0'}" class="correct-value">
            </div>
        `;
        
        document.getElementById('options-list').appendChild(optionDiv);
        
        const setCorrectBtn = optionDiv.querySelector('.set-correct-btn');
        setCorrectBtn.addEventListener('click', function() {
            handleSetCorrect(this, optionDiv);
        });
        
        optionDiv.querySelector('.remove-option').addEventListener('click', function() {
            if (isTrueFalse) {
                alert('True/False questions must have exactly 2 options. You cannot remove options.');
                return;
            }
            if (document.querySelectorAll('.option-card').length <= 2) {
                alert('You need at least 2 options');
                return;
            }
            optionDiv.remove();
        });
        
        if (isCorrect) {
            handleSetCorrect(setCorrectBtn, optionDiv);
        }
    }
    
    function handleSetCorrect(button, optionDiv) {
        const isMultiple = button.dataset.questionType === 'multiple_choice';
        
        if (isMultiple) {
            // Multiple choice - toggle correct status
            const currentValue = optionDiv.querySelector('.correct-value').value;
            const isCurrentlyCorrect = currentValue === '1';
            
            if (isCurrentlyCorrect) {
                // Unmark as correct
                button.classList.remove('btn-correct-selected');
                button.classList.add('btn-secondary');
                button.textContent = 'Set Correct';
                optionDiv.querySelector('.correct-value').value = '0';
                optionDiv.classList.remove('correct-option');
            } else {
                // Mark as correct
                button.classList.remove('btn-secondary');
                button.classList.add('btn-correct-selected');
                button.textContent = '✓ Correct';
                optionDiv.querySelector('.correct-value').value = '1';
                optionDiv.classList.add('correct-option');
            }
        } else {
            // Single choice or True/False - only one can be correct
            document.querySelectorAll('.set-correct-btn').forEach(btn => {
                btn.classList.remove('btn-correct-selected');
                btn.classList.add('btn-secondary');
                btn.textContent = 'Set Correct';
            });
            document.querySelectorAll('.correct-value').forEach(input => input.value = '0');
            document.querySelectorAll('.option-card').forEach(card => card.classList.remove('correct-option'));
            
            button.classList.remove('btn-secondary');
            button.classList.add('btn-correct-selected');
            button.textContent = '✓ Correct';
            optionDiv.querySelector('.correct-value').value = '1';
            optionDiv.classList.add('correct-option');
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Add option button
    document.getElementById('add-option').addEventListener('click', function() {
        if (isTrueFalse) {
            alert('True/False questions can only have 2 options (True and False). You cannot add more options.');
            return;
        }
        addOption('', false);
    });
    
    // Initialize existing remove buttons
    document.querySelectorAll('.remove-option-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (isTrueFalse) {
                alert('True/False questions must have exactly 2 options. You cannot remove options.');
                return;
            }
            if (document.querySelectorAll('.option-card').length <= 2) {
                alert('You need at least 2 options');
                return;
            }
            this.closest('.option-card').remove();
        });
    });
    
    // Initialize existing set correct buttons
    document.querySelectorAll('.set-correct-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const optionCard = this.closest('.option-card');
            handleSetCorrect(this, optionCard);
        });
    });
    
    // Form validation before submit
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        let correctCount = 0;
        document.querySelectorAll('.correct-value').forEach(input => {
            if (input.value === '1') correctCount++;
        });
        
        if (correctCount === 0) {
            e.preventDefault();
            alert('Please select at least one correct answer by clicking "Set Correct" on the option(s)');
            return false;
        }
        
        // For single choice and true/false, ensure only one correct answer
        if ((isSingleChoice || isTrueFalse) && correctCount > 1) {
            e.preventDefault();
            alert('For ' + (isTrueFalse ? 'True/False' : 'Single Choice') + ' questions, only ONE option can be marked as correct.');
            return false;
        }
        
        let allFilled = true;
        document.querySelectorAll('.option-card input[type="text"]').forEach(input => {
            if (!input.value.trim()) allFilled = false;
        });
        
        if (!allFilled) {
            e.preventDefault();
            alert('Please fill in all option texts');
            return false;
        }
        
        // For True/False, ensure exactly 2 options
        if (isTrueFalse && document.querySelectorAll('.option-card').length !== 2) {
            e.preventDefault();
            alert('True/False questions must have exactly 2 options (True and False)');
            return false;
        }
    });
    
    // Disable option text editing for True/False? (optional - uncomment if needed)
    @if($question->question_type == 'true_false')
    // For True/False, make option text read-only
    document.querySelectorAll('.option-card input[type="text"]').forEach(input => {
        input.readOnly = true;
        input.style.backgroundColor = '#e9ecef';
    });
    @endif
</script>
@endpush
@endsection