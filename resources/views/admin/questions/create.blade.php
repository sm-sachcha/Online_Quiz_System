@extends('layouts.admin')

@section('title', 'Add Question - ' . $quiz->title)

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
        position: relative;
    }
    .option-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .btn-set-correct {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        font-size: 14px;
        white-space: nowrap;
    }
    .btn-set-correct:hover {
        background-color: #5a6268;
        transform: translateY(-1px);
    }
    .btn-correct-selected {
        background-color: #28a745 !important;
        color: white !important;
    }
    .btn-correct-selected:hover {
        background-color: #218838 !important;
    }
    .remove-option-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        opacity: 0.7;
    }
    .remove-option-btn:hover {
        opacity: 1;
        transform: scale(1.1);
        background-color: #c82333;
    }
    .option-card .card-body {
        padding: 12px 15px !important;
    }
    .option-letter {
        background-color: #6c757d;
        color: white;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
    }
    .option-text-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 14px;
    }
    .option-text-input:focus {
        outline: none;
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
</style>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus"></i> Add New Question to: {{ $quiz->title }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quizzes.questions.store', $quiz) }}" method="POST" id="questionForm">
                    @csrf

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('question_text') is-invalid @enderror" 
                                  id="question_text" name="question_text" rows="3" required>{{ old('question_text') }}</textarea>
                        @error('question_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('question_type') is-invalid @enderror" 
                                    id="question_type" name="question_type" required>
                                <option value="">Select Type</option>
                                <option value="multiple_choice" {{ old('question_type') == 'multiple_choice' ? 'selected' : '' }}>Multiple Choice (Multiple Correct Answers)</option>
                                <option value="single_choice" {{ old('question_type') == 'single_choice' ? 'selected' : '' }}>Single Choice (One Correct Answer)</option>
                                <option value="true_false" {{ old('question_type') == 'true_false' ? 'selected' : '' }}>True / False</option>
                            </select>
                            @error('question_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="points" class="form-label">Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('points') is-invalid @enderror" 
                                   id="points" name="points" value="{{ old('points', 10) }}" min="1" max="100" required>
                            @error('points')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="time_seconds" class="form-label">Time Limit <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('time_seconds') is-invalid @enderror" 
                                   id="time_seconds" name="time_seconds" value="{{ old('time_seconds', 30) }}" min="10" max="300" required>
                            @error('time_seconds')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Options Container -->
                    <div id="options-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-bold">Answer Options <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-success" id="add-option">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                        
                        <div id="options-list">
                            <!-- Options will be added here dynamically -->
                        </div>
                    </div>

                    <!-- True/False Container -->
                    <div id="true-false-container" style="display: none;">
                        <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="true_false_correct" id="true_option" value="true">
                                            <label class="form-check-label" for="true_option">True</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="true_false_correct" id="false_option" value="false">
                                            <label class="form-check-label" for="false_option">False</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="explanation" class="form-label">Explanation (Optional)</label>
                        <textarea class="form-control @error('explanation') is-invalid @enderror" 
                                  id="explanation" name="explanation" rows="2">{{ old('explanation') }}</textarea>
                        @error('explanation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Show Answer Option -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show_answer" name="show_answer" value="1" checked>
                            <label class="form-check-label" for="show_answer">
                                <i class="fas fa-eye"></i> Show correct answer to users after they answer
                            </label>
                            <div class="form-text text-muted">
                                If enabled, users will see the correct answer after they submit their response.
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning" id="warning-alert">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong id="warning-text">For Single Choice, click the "✓ Set as Correct" button on the correct option.</strong>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fas fa-save"></i> Create Question
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
    let optionCount = 0;
    let currentType = 'single_choice';
    
    function addOption(optionText = '', isCorrect = false) {
        optionCount++;
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-card card mb-2';
        optionDiv.setAttribute('data-option-index', optionCount);
        
        if (currentType === 'multiple_choice') {
            optionDiv.innerHTML = `
                <div class="card-body">
                    <div class="row align-items-center g-2">
                        <div class="col-auto">
                            <div class="option-letter">${String.fromCharCode(64 + optionCount)}</div>
                        </div>
                        <div class="col">
                            <input type="text" class="option-text-input" 
                                   name="options[${optionCount}][text]" 
                                   value="${escapeHtml(optionText)}" 
                                   placeholder="Enter option text" required>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input correct-checkbox" type="checkbox" 
                                       name="options[${optionCount}][is_correct]" 
                                       value="1" 
                                       ${isCorrect ? 'checked' : ''}
                                       style="width: 20px; height: 20px;">
                                <label class="form-check-label ms-1">Correct</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="remove-option-btn" data-option-idx="${optionCount}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="options[${optionCount}][order]" value="${optionCount}">
                </div>
            `;
            
            const checkbox = optionDiv.querySelector('.correct-checkbox');
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    optionDiv.classList.add('correct-option');
                } else {
                    optionDiv.classList.remove('correct-option');
                }
            });
            if (isCorrect) {
                optionDiv.classList.add('correct-option');
            }
        } else {
            // Single Choice - Button on the right side
            optionDiv.innerHTML = `
                <div class="card-body">
                    <div class="row align-items-center g-2">
                        <div class="col-auto">
                            <div class="option-letter">${String.fromCharCode(64 + optionCount)}</div>
                        </div>
                        <div class="col">
                            <input type="text" class="option-text-input" 
                                   name="options[${optionCount}][text]" 
                                   value="${escapeHtml(optionText)}" 
                                   placeholder="Enter option text" required>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn-set-correct set-correct-btn" 
                                    data-option-index="${optionCount}">
                                <i class="fas fa-times-circle"></i> Set as Correct
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="remove-option-btn" data-option-idx="${optionCount}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="options[${optionCount}][is_correct]" value="0" class="correct-value">
                    <input type="hidden" name="options[${optionCount}][order]" value="${optionCount}">
                </div>
            `;
        }
        
        document.getElementById('options-list').appendChild(optionDiv);
        
        // Add remove handler
        const removeBtn = optionDiv.querySelector('.remove-option-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const optionsCount = document.querySelectorAll('.option-card').length;
                if (optionsCount <= 2) {
                    alert('You need at least 2 options');
                    return;
                }
                optionDiv.remove();
                updateOptionLetters();
            });
        }
        
        // Add single choice set correct handler
        if (currentType !== 'multiple_choice') {
            const setCorrectBtn = optionDiv.querySelector('.set-correct-btn');
            if (setCorrectBtn) {
                setCorrectBtn.addEventListener('click', function() {
                    // Reset all buttons
                    document.querySelectorAll('.set-correct-btn').forEach(btn => {
                        btn.classList.remove('btn-correct-selected');
                        btn.innerHTML = '<i class="fas fa-times-circle"></i> Set as Correct';
                    });
                    
                    // Reset all correct values
                    document.querySelectorAll('.correct-value').forEach(input => {
                        input.value = '0';
                    });
                    
                    // Reset all option cards highlight
                    document.querySelectorAll('.option-card').forEach(card => {
                        card.classList.remove('correct-option');
                    });
                    
                    // Set this option as correct
                    this.classList.add('btn-correct-selected');
                    this.innerHTML = '<i class="fas fa-check-circle"></i> Correct Answer ✓';
                    
                    const optionIndex = this.getAttribute('data-option-index');
                    const correctInput = optionDiv.querySelector('.correct-value');
                    if (correctInput) {
                        correctInput.value = '1';
                    }
                    
                    // Highlight this option
                    optionDiv.classList.add('correct-option');
                });
                
                if (isCorrect) {
                    setCorrectBtn.click();
                }
            }
        }
    }
    
    function updateOptionLetters() {
        const options = document.querySelectorAll('.option-card');
        options.forEach((option, index) => {
            const letterDiv = option.querySelector('.option-letter');
            if (letterDiv) {
                letterDiv.textContent = String.fromCharCode(65 + index);
            }
            const setCorrectBtn = option.querySelector('.set-correct-btn');
            if (setCorrectBtn) {
                setCorrectBtn.setAttribute('data-option-index', index + 1);
            }
            const removeBtn = option.querySelector('.remove-option-btn');
            if (removeBtn) {
                removeBtn.setAttribute('data-option-idx', index + 1);
            }
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    const typeSelect = document.getElementById('question_type');
    const optionsDiv = document.getElementById('options-container');
    const trueFalseDiv = document.getElementById('true-false-container');
    const warningText = document.getElementById('warning-text');
    
    typeSelect.addEventListener('change', function() {
        currentType = this.value;
        document.getElementById('options-list').innerHTML = '';
        optionCount = 0;
        
        if (this.value === 'true_false') {
            optionsDiv.style.display = 'none';
            trueFalseDiv.style.display = 'block';
            warningText.innerHTML = 'For True/False questions, select either True or False as the correct answer.';
        } else if (this.value === 'multiple_choice') {
            optionsDiv.style.display = 'block';
            trueFalseDiv.style.display = 'none';
            warningText.innerHTML = 'For Multiple Choice questions, check the box next to each correct answer. You can select multiple correct answers.';
            addOption('', false);
            addOption('', false);
            addOption('', false);
            addOption('', false);
        } else if (this.value === 'single_choice') {
            optionsDiv.style.display = 'block';
            trueFalseDiv.style.display = 'none';
            warningText.innerHTML = 'For Single Choice questions, click the "✓ Set as Correct" button on the correct option. Only ONE option can be correct.';
            addOption('', false);
            addOption('', false);
            addOption('', false);
            addOption('', false);
        }
    });
    
    document.getElementById('add-option').addEventListener('click', function() {
        addOption('', false);
    });
    
    // Initialize for single choice (default)
    addOption('', false);
    addOption('', false);
    addOption('', false);
    addOption('', false);
    
    // Form validation
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        const type = typeSelect.value;
        
        if (type === 'multiple_choice') {
            let hasCorrect = false;
            document.querySelectorAll('.correct-checkbox').forEach(cb => {
                if (cb.checked) hasCorrect = true;
            });
            if (!hasCorrect) {
                e.preventDefault();
                alert('❌ Please select at least one correct answer by checking the checkbox.');
                return false;
            }
        } else if (type === 'single_choice') {
            let correctCount = 0;
            document.querySelectorAll('.correct-value').forEach(input => {
                if (input.value === '1') {
                    correctCount++;
                }
            });
            if (correctCount === 0) {
                e.preventDefault();
                alert('❌ Please select ONE correct answer by clicking the "✓ Set as Correct" button on the correct option.');
                return false;
            }
            if (correctCount > 1) {
                e.preventDefault();
                alert('❌ For Single Choice questions, you can only select ONE correct answer.');
                return false;
            }
        } else if (type === 'true_false') {
            if (!document.querySelector('input[name="true_false_correct"]:checked')) {
                e.preventDefault();
                alert('❌ Please select True or False as the correct answer.');
                return false;
            }
        }
        
        // Check if all options have text
        let allFilled = true;
        let emptyOptions = [];
        document.querySelectorAll('.option-text-input').forEach((input, index) => {
            if (!input.value.trim()) {
                allFilled = false;
                emptyOptions.push(index + 1);
            }
        });
        
        if (!allFilled) {
            e.preventDefault();
            alert(`❌ Please fill in all option texts. Empty options found at: ${emptyOptions.join(', ')}`);
            return false;
        }
        
        // Show loading
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Question...';
    });
</script>
@endpush
@endsection