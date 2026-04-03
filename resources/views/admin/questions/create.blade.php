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
    }
    .option-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    }
    .correct-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .question-type-info {
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .info-multiple {
        background-color: #d1ecf1;
        border-left: 4px solid #17a2b8;
        color: #0c5460;
    }
    .info-single {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        color: #856404;
    }
    .info-truefalse {
        background-color: #e2e3e5;
        border-left: 4px solid #6c757d;
        color: #383d41;
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
                                <option value="">-- Select Question Type --</option>
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
                            <label for="time_seconds" class="form-label">Time Limit (seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('time_seconds') is-invalid @enderror" 
                                   id="time_seconds" name="time_seconds" value="{{ old('time_seconds', 30) }}" min="10" max="300" required>
                            @error('time_seconds')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Question Type Info Message -->
                    <div id="typeInfo" class="question-type-info" style="display: none;">
                        <i class="fas fa-info-circle"></i> <span id="typeInfoText"></span>
                    </div>

                    <!-- Options Container (for multiple_choice and single_choice) -->
                    <div id="optionsContainer" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-bold">Answer Options <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-success" id="addOptionBtn">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                        <div id="optionsList">
                            <!-- Options will be added here dynamically -->
                        </div>
                    </div>

                    <!-- True/False Container -->
                    <div id="trueFalseContainer" style="display: none;">
                        <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="true_false_correct" id="true_option" value="true">
                                            <label class="form-check-label" for="true_option">
                                                <i class="fas fa-check-circle text-success fa-2x"></i>
                                                <h5 class="mt-2">True</h5>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="true_false_correct" id="false_option" value="false">
                                            <label class="form-check-label" for="false_option">
                                                <i class="fas fa-times-circle text-danger fa-2x"></i>
                                                <h5 class="mt-2">False</h5>
                                            </label>
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
    let optionCounter = 0;
    let currentType = '';

    const typeSelect = document.getElementById('question_type');
    const optionsContainer = document.getElementById('optionsContainer');
    const trueFalseContainer = document.getElementById('trueFalseContainer');
    const optionsList = document.getElementById('optionsList');
    const typeInfo = document.getElementById('typeInfo');
    const typeInfoText = document.getElementById('typeInfoText');
    const addOptionBtn = document.getElementById('addOptionBtn');

    function addOption(optionText = '', isCorrect = false) {
        optionCounter++;
        const isMultiple = currentType === 'multiple_choice';
        
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-card card mb-2';
        optionDiv.setAttribute('data-option-idx', optionCounter);
        
        if (isMultiple) {
            optionDiv.innerHTML = `
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="option-letter">${String.fromCharCode(64 + optionCounter)}</div>
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" 
                                   name="options[${optionCounter}][text]" 
                                   value="${escapeHtml(optionText)}" 
                                   placeholder="Enter option text" required>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input correct-checkbox" type="checkbox" 
                                       name="options[${optionCounter}][is_correct]" 
                                       value="1" 
                                       ${isCorrect ? 'checked' : ''}>
                                <label class="form-check-label ms-1">Correct</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="remove-option-btn" data-option-idx="${optionCounter}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="options[${optionCounter}][order]" value="${optionCounter}">
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
            if (isCorrect) optionDiv.classList.add('correct-option');
        } else {
            optionDiv.innerHTML = `
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="option-letter">${String.fromCharCode(64 + optionCounter)}</div>
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" 
                                   name="options[${optionCounter}][text]" 
                                   value="${escapeHtml(optionText)}" 
                                   placeholder="Enter option text" required>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-secondary set-correct-btn" 
                                    data-option-index="${optionCounter}">
                                <i class="fas fa-times-circle"></i> Set as Correct
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="remove-option-btn" data-option-idx="${optionCounter}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="options[${optionCounter}][is_correct]" value="0" class="correct-value">
                    <input type="hidden" name="options[${optionCounter}][order]" value="${optionCounter}">
                </div>
            `;
            
            const setCorrectBtn = optionDiv.querySelector('.set-correct-btn');
            setCorrectBtn.addEventListener('click', function() {
                document.querySelectorAll('.set-correct-btn').forEach(btn => {
                    btn.classList.remove('btn-correct-selected');
                    btn.classList.add('btn-secondary');
                    btn.innerHTML = '<i class="fas fa-times-circle"></i> Set as Correct';
                });
                document.querySelectorAll('.correct-value').forEach(input => input.value = '0');
                document.querySelectorAll('.option-card').forEach(card => card.classList.remove('correct-option'));
                
                this.classList.remove('btn-secondary');
                this.classList.add('btn-correct-selected');
                this.innerHTML = '<i class="fas fa-check-circle"></i> Correct Answer ✓';
                optionDiv.querySelector('.correct-value').value = '1';
                optionDiv.classList.add('correct-option');
            });
            
            if (isCorrect) setCorrectBtn.click();
        }
        
        optionsList.appendChild(optionDiv);
        
        const removeBtn = optionDiv.querySelector('.remove-option-btn');
        removeBtn.addEventListener('click', function() {
            if (document.querySelectorAll('.option-card').length <= 2) {
                alert('You need at least 2 options');
                return;
            }
            optionDiv.remove();
            updateOptionLetters();
        });
    }
    
    function updateOptionLetters() {
        const options = document.querySelectorAll('.option-card');
        options.forEach((option, index) => {
            const letterDiv = option.querySelector('.option-letter');
            if (letterDiv) letterDiv.textContent = String.fromCharCode(65 + index);
            const setCorrectBtn = option.querySelector('.set-correct-btn');
            if (setCorrectBtn) setCorrectBtn.setAttribute('data-option-index', index + 1);
        });
    }
    
    function resetOptions() {
        optionsList.innerHTML = '';
        optionCounter = 0;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function loadQuestionType() {
        const selectedType = typeSelect.value;
        if (!selectedType) {
            optionsContainer.style.display = 'none';
            trueFalseContainer.style.display = 'none';
            typeInfo.style.display = 'none';
            return;
        }
        
        currentType = selectedType;
        optionsContainer.style.display = 'none';
        trueFalseContainer.style.display = 'none';
        typeInfo.style.display = 'block';
        
        if (selectedType === 'multiple_choice') {
            optionsContainer.style.display = 'block';
            typeInfo.className = 'question-type-info info-multiple';
            typeInfoText.innerHTML = 'Multiple Choice: You can select MULTIPLE correct answers by checking the box next to each correct option.';
            resetOptions();
            addOption('', false);
            addOption('', false);
            addOption('', false);
            addOption('', false);
        } 
        else if (selectedType === 'single_choice') {
            optionsContainer.style.display = 'block';
            typeInfo.className = 'question-type-info info-single';
            typeInfoText.innerHTML = 'Single Choice: Only ONE option can be marked as correct. Click the "Set as Correct" button on the correct option.';
            resetOptions();
            addOption('', false);
            addOption('', false);
            addOption('', false);
            addOption('', false);
        } 
        else if (selectedType === 'true_false') {
            trueFalseContainer.style.display = 'block';
            typeInfo.className = 'question-type-info info-truefalse';
            typeInfoText.innerHTML = 'True/False: Select either True or False as the correct answer.';
        }
    }
    
    typeSelect.addEventListener('change', loadQuestionType);
    
    addOptionBtn.addEventListener('click', function() {
        if (currentType === 'multiple_choice' || currentType === 'single_choice') {
            addOption('', false);
        } else {
            alert('Please select a question type first');
        }
    });
    
    if (typeSelect.value) {
        loadQuestionType();
    }
    
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        const type = typeSelect.value;
        
        if (!type) {
            e.preventDefault();
            alert('Please select a question type');
            return false;
        }
        
        if (type === 'multiple_choice') {
            let hasCorrect = false;
            document.querySelectorAll('.correct-checkbox').forEach(cb => {
                if (cb.checked) hasCorrect = true;
            });
            if (!hasCorrect) {
                e.preventDefault();
                alert('Please select at least one correct answer by checking the checkbox.');
                return false;
            }
        } 
        else if (type === 'single_choice') {
            let correctCount = 0;
            document.querySelectorAll('.correct-value').forEach(input => {
                if (input.value === '1') correctCount++;
            });
            if (correctCount === 0) {
                e.preventDefault();
                alert('Please select ONE correct answer by clicking the "Set as Correct" button.');
                return false;
            }
            if (correctCount > 1) {
                e.preventDefault();
                alert('For Single Choice questions, only ONE correct answer is allowed.');
                return false;
            }
        } 
        else if (type === 'true_false') {
            const trueChecked = document.getElementById('true_option').checked;
            const falseChecked = document.getElementById('false_option').checked;
            if (!trueChecked && !falseChecked) {
                e.preventDefault();
                alert('Please select True or False as the correct answer.');
                return false;
            }
        }
        
        if (type !== 'true_false') {
            let allFilled = true;
            document.querySelectorAll('.option-card input[type="text"]').forEach(input => {
                if (!input.value.trim()) allFilled = false;
            });
            if (!allFilled) {
                e.preventDefault();
                alert('Please fill in all option texts');
                return false;
            }
        }
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Question...';
    });
</script>
@endpush
@endsection