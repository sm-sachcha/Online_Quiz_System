@extends('layouts.admin')

@section('title', 'Add Question - ' . $quiz->title)

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus"></i> Add New Question to: {{ $quiz->title }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quizzes.questions.store', $quiz) }}" method="POST" id="questionForm">
                    @csrf

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question <span class="text-danger">*</span></label>
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
                                <option value="multiple_choice" {{ old('question_type') == 'multiple_choice' ? 'selected' : '' }}>Multiple Choice</option>
                                <option value="single_choice" {{ old('question_type') == 'single_choice' ? 'selected' : '' }}>Single Choice</option>
                                <option value="true_false" {{ old('question_type') == 'true_false' ? 'selected' : '' }}>True/False</option>
                            </select>
                            @error('question_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="points" class="form-label">Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('points') is-invalid @enderror" 
                                   id="points" name="points" value="{{ old('points', 1) }}" min="1" max="100" required>
                            @error('points')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="time_seconds" class="form-label">Time (seconds) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('time_seconds') is-invalid @enderror" 
                                   id="time_seconds" name="time_seconds" value="{{ old('time_seconds', 30) }}" min="5" max="300" required>
                            @error('time_seconds')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div id="options-container">
                        <label class="form-label">Options <span class="text-danger">*</span></label>
                        <div class="mb-2 text-muted small">
                            <i class="fas fa-info-circle"></i> Check the correct answer(s). For single choice, only one option should be correct.
                        </div>
                        
                        <div id="options-list">
                            <!-- Options will be added here dynamically -->
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-success mt-2" id="add-option">
                            <i class="fas fa-plus"></i> Add Option
                        </button>
                    </div>

                    <div id="true-false-container" style="display: none;">
                        <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="true_false_correct" id="true_option" value="true">
                                <label class="form-check-label" for="true_option">True</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="true_false_correct" id="false_option" value="false">
                                <label class="form-check-label" for="false_option">False</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="explanation" class="form-label">Explanation (Optional)</label>
                        <textarea class="form-control @error('explanation') is-invalid @enderror" 
                                  id="explanation" name="explanation" rows="2">{{ old('explanation') }}</textarea>
                        <small class="text-muted">This will be shown to users after they answer the question.</small>
                        @error('explanation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
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
    const optionsContainer = document.getElementById('options-list');
    const questionType = document.getElementById('question_type');
    const optionsDiv = document.getElementById('options-container');
    const trueFalseDiv = document.getElementById('true-false-container');

    function addOption(optionText = '', isCorrect = false) {
        optionCount++;
        const optionDiv = document.createElement('div');
        optionDiv.className = 'row mb-2 option-row';
        optionDiv.innerHTML = `
            <div class="col-md-1">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="options[${optionCount}][is_correct]" 
                           ${isCorrect ? 'checked' : ''}>
                </div>
            </div>
            <div class="col-md-10">
                <input type="text" class="form-control" name="options[${optionCount}][text]" 
                       value="${optionText.replace(/"/g, '&quot;')}" placeholder="Option text" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger remove-option">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        optionsContainer.appendChild(optionDiv);
        
        optionDiv.querySelector('.remove-option').addEventListener('click', function() {
            optionDiv.remove();
        });
    }

    questionType.addEventListener('change', function() {
        if (this.value === 'true_false') {
            optionsDiv.style.display = 'none';
            trueFalseDiv.style.display = 'block';
        } else {
            optionsDiv.style.display = 'block';
            trueFalseDiv.style.display = 'none';
            
            if (optionsContainer.children.length === 0) {
                addOption();
                addOption();
            }
        }
    });

    document.getElementById('add-option').addEventListener('click', function() {
        addOption();
    });

    // Initialize based on selected type
    if (questionType.value === 'multiple_choice' || questionType.value === 'single_choice') {
        addOption();
        addOption();
    } else if (questionType.value === 'true_false') {
        optionsDiv.style.display = 'none';
        trueFalseDiv.style.display = 'block';
    }
</script>
@endpush
@endsection