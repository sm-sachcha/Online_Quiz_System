@extends('layouts.admin')

@section('title', 'Create Quiz')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus"></i> Create New Quiz</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quizzes.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="duration_minutes" class="form-label">Total Duration (Minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" 
                                   id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', 30) }}" 
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
                                   id="passing_score" name="passing_score" value="{{ old('passing_score', 50) }}" 
                                   min="0" max="100" required>
                            @error('passing_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="max_attempts" class="form-label">Max Attempts <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_attempts') is-invalid @enderror" 
                                   id="max_attempts" name="max_attempts" value="{{ old('max_attempts', 1) }}" 
                                   min="1" max="10" required>
                            @error('max_attempts')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_at" class="form-label">Schedule Start (Optional)</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                            <small class="text-muted">Leave blank to start immediately</small>
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="ends_at" class="form-label">End Date (Optional)</label>
                            <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" 
                                   id="ends_at" name="ends_at" value="{{ old('ends_at') }}">
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
                                       {{ old('is_random_questions') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_random_questions">
                                    Randomize Question Order
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_published" 
                                       name="is_published" value="1" 
                                       {{ old('is_published') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_published">
                                    Publish Immediately
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> After creating the quiz, you'll be able to add questions with individual points and time limits.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Create Quiz & Add Questions
                        </button>
                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection