@extends('layouts.admin')

@section('title', 'Edit Question')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Question</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quizzes.questions.update', [$quiz, $question]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('question_text') is-invalid @enderror" 
                                  id="question_text" name="question_text" rows="3" required>{{ old('question_text', $question->question_text) }}</textarea>
                        @error('question_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Question Type</label>
                            <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}" readonly>
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
                                   id="time_seconds" name="time_seconds" value="{{ old('time_seconds', $question->time_seconds) }}" min="5" max="300" required>
                            @error('time_seconds')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Options <span class="text-danger">*</span></label>
                        @foreach($question->options as $index => $option)
                            <div class="row mb-2">
                                <div class="col-md-1">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="options[{{ $index }}][is_correct]" 
                                               {{ $option->is_correct ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option->id }}">
                                    <input type="text" class="form-control" name="options[{{ $index }}][text]" 
                                           value="{{ $option->option_text }}" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-sm btn-danger remove-option" data-id="{{ $option->id }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <label for="explanation" class="form-label">Explanation (Optional)</label>
                        <textarea class="form-control @error('explanation') is-invalid @enderror" 
                                  id="explanation" name="explanation" rows="2">{{ old('explanation', $question->explanation) }}</textarea>
                        @error('explanation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
    document.querySelectorAll('.remove-option').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.row').remove();
        });
    });
</script>
@endpush
@endsection