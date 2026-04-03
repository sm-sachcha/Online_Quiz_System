@extends('layouts.admin')

@section('title', 'Edit Quiz')

@section('content')
<style>
    .datetime-wrapper {
        position: relative;
    }
    .reset-datetime {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #dc3545;
        background: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        z-index: 10;
    }
    .reset-datetime:hover {
        background-color: #dc3545;
        color: white;
        transform: translateY(-50%) scale(1.1);
    }
    .datetime-input {
        padding-right: 35px !important;
    }
    .reset-icon {
        font-size: 12px;
    }
    .start-quiz-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        transition: all 0.3s;
    }
    .start-quiz-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    }
</style>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Quiz: {{ $quiz->title }}</h5>
                @if(!$quiz->is_published || ($quiz->scheduled_at && $quiz->scheduled_at > now()))
                    <form action="{{ route('admin.quizzes.start', $quiz) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm start-quiz-btn" onclick="return confirm('Start this quiz now? Participants will be able to take it immediately.')">
                            <i class="fas fa-play"></i> Start Quiz Now
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quizzes.update', $quiz) }}" method="POST" id="quizForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title', $quiz->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $quiz->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-muted">(Optional)</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" name="category_id">
                                <option value="">-- No Category --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $quiz->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">You can leave this empty to create a quiz without a category</small>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="duration_minutes" class="form-label">Total Duration (Minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" 
                                   id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $quiz->duration_minutes) }}" 
                                   min="1" max="480" required>
                            <small class="text-muted">Total time allowed for the entire quiz</small>
                            @error('duration_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="passing_score" class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('passing_score') is-invalid @enderror" 
                                   id="passing_score" name="passing_score" value="{{ old('passing_score', $quiz->passing_score) }}" 
                                   min="0" max="100" required>
                            @error('passing_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="max_attempts" class="form-label">Max Attempts <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_attempts') is-invalid @enderror" 
                                   id="max_attempts" name="max_attempts" value="{{ old('max_attempts', $quiz->max_attempts) }}" 
                                   min="1" max="10" required>
                            @error('max_attempts')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_at" class="form-label">Schedule Start</label>
                            <div class="datetime-wrapper">
                                <input type="datetime-local" class="form-control datetime-input @error('scheduled_at') is-invalid @enderror" 
                                       id="scheduled_at" name="scheduled_at" 
                                       value="{{ old('scheduled_at', $quiz->scheduled_at ? $quiz->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                                <span class="reset-datetime" onclick="resetDateTime('scheduled_at')" title="Clear schedule start">
                                    <i class="fas fa-times-circle reset-icon"></i>
                                </span>
                            </div>
                            <small class="text-muted">Leave blank to start immediately when published</small>
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="ends_at" class="form-label">End Date</label>
                            <div class="datetime-wrapper">
                                <input type="datetime-local" class="form-control datetime-input @error('ends_at') is-invalid @enderror" 
                                       id="ends_at" name="ends_at" 
                                       value="{{ old('ends_at', $quiz->ends_at ? $quiz->ends_at->format('Y-m-d\TH:i') : '') }}">
                                <span class="reset-datetime" onclick="resetDateTime('ends_at')" title="Clear end date">
                                    <i class="fas fa-times-circle reset-icon"></i>
                                </span>
                            </div>
                            <small class="text-muted">Leave blank for no expiry</small>
                            @error('ends_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_random_questions" 
                                       name="is_random_questions" value="1" 
                                       {{ old('is_random_questions', $quiz->is_random_questions) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_random_questions">
                                    Randomize Question Order
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_published" 
                                       name="is_published" value="1" 
                                       {{ old('is_published', $quiz->is_published) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_published">
                                    Publish Quiz
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Current Statistics:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Total Questions: {{ $quiz->questions()->count() }}</li>
                            <li>Total Points: {{ $quiz->questions()->sum('points') }}</li>
                            <li>Total Attempts: {{ $quiz->attempts()->count() }}</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Quiz Status:</strong>
                        @if($quiz->is_published)
                            @if($quiz->scheduled_at && $quiz->scheduled_at > now())
                                <span class="badge bg-warning">Scheduled to start at {{ $quiz->scheduled_at->format('M d, Y h:i A') }}</span>
                            @elseif($quiz->ends_at && $quiz->ends_at < now())
                                <span class="badge bg-danger">Expired</span>
                            @else
                                <span class="badge bg-success">Published and Active</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">Draft - Click "Start Quiz Now" to activate</span>
                        @endif
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Quiz
                        </button>
                        <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="btn btn-info">
                            <i class="fas fa-question-circle"></i> Manage Questions
                        </a>
                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary">
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
    function resetDateTime(fieldId) {
        const input = document.getElementById(fieldId);
        if (input) {
            input.value = '';
            // Add visual feedback
            const resetBtn = input.nextElementSibling;
            if (resetBtn) {
                resetBtn.style.transform = 'translateY(-50%) scale(1.2)';
                setTimeout(() => {
                    resetBtn.style.transform = 'translateY(-50%) scale(1)';
                }, 200);
            }
        }
    }
    
    // Add hover effect for reset buttons
    document.querySelectorAll('.reset-datetime').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-50%) scale(1.1)';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-50%) scale(1)';
        });
    });
    
    // Warn if publishing without questions
    const publishCheckbox = document.getElementById('is_published');
    const form = document.getElementById('quizForm');
    
    if (publishCheckbox) {
        form.addEventListener('submit', function(e) {
            if (publishCheckbox.checked) {
                const questionCount = {{ $quiz->questions()->count() }};
                if (questionCount === 0) {
                    e.preventDefault();
                    alert('Please add at least one question before publishing the quiz.');
                    return false;
                }
            }
        });
    }
</script>
@endpush
@endsection