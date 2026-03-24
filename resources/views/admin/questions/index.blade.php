@extends('layouts.admin')

@section('title', 'Manage Questions - ' . $quiz->title)

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Questions for: {{ $quiz->title }}</h5>
                <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Question
                </a>
            </div>
            <div class="card-body">
                @if($questions->count() > 0)
                    <div class="alert alert-info">
                        <i class="fas fa-arrows-alt"></i> <strong>Drag and drop</strong> to reorder questions.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Order</th>
                                    <th>Question</th>
                                    <th style="width: 100px;">Type</th>
                                    <th style="width: 80px;">Points</th>
                                    <th style="width: 80px;">Time</th>
                                    <th style="width: 200px;">Options</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable">
                                @foreach($questions as $question)
                                    <tr data-id="{{ $question->id }}">
                                        <td class="handle" style="cursor: move;">
                                            <i class="fas fa-grip-vertical"></i> {{ $question->order }}
                                        </td>
                                        <td>
                                            <strong>{{ Str::limit($question->question_text, 80) }}</strong>
                                            @if($question->explanation)
                                                <br><small class="text-muted"><i class="fas fa-info-circle"></i> Has explanation</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($question->question_type == 'multiple_choice')
                                                <span class="badge bg-primary">Multiple Choice</span>
                                            @elseif($question->question_type == 'true_false')
                                                <span class="badge bg-info">True/False</span>
                                            @else
                                                <span class="badge bg-secondary">Single Choice</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-success">{{ $question->points }}</span></td>
                                        <td>{{ $question->time_seconds }}s</td>
                                        <td>
                                            @foreach($question->options as $option)
                                                <div class="small mb-1">
                                                    @if($option->is_correct)
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                        <i class="fas fa-circle text-muted"></i>
                                                    @endif
                                                    {{ Str::limit($option->option_text, 30) }}
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.quizzes.questions.edit', [$quiz, $question]) }}" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.quizzes.questions.destroy', [$quiz, $question]) }}" 
                                                      method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            title="Delete"
                                                            onclick="return confirm('Are you sure you want to delete this question?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Quiz
                        </a>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                        <h5>No Questions Yet</h5>
                        <p class="text-muted">Add questions to make this quiz available to users.</p>
                        <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Question
                        </a>
                        <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-secondary">
                            Back to Quiz
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const sortable = new Sortable(document.getElementById('sortable'), {
        handle: '.handle',
        animation: 150,
        onEnd: function() {
            const questions = [];
            document.querySelectorAll('#sortable tr').forEach((row, index) => {
                questions.push({
                    id: row.dataset.id,
                    order: index + 1
                });
            });
            
            fetch('{{ route("admin.quizzes.questions.reorder", $quiz) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ questions: questions })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update order numbers
                    document.querySelectorAll('#sortable tr .handle').forEach((el, idx) => {
                        el.innerHTML = '<i class="fas fa-grip-vertical"></i> ' + (idx + 1);
                    });
                }
            });
        }
    });
</script>
@endpush
@endsection