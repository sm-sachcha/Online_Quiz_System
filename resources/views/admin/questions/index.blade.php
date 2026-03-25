@extends('layouts.admin')

@section('title', 'Manage Questions - ' . $quiz->title)

@section('content')
<style>
    .handle {
        cursor: move;
        color: #6c757d;
    }
    .handle:hover {
        color: #007bff;
    }
    .question-row {
        transition: all 0.3s ease;
    }
    .question-row:hover {
        background-color: #f8f9fa;
    }
    .sortable-ghost {
        opacity: 0.5;
        background-color: #e9ecef;
    }
    .sortable-drag {
        opacity: 0.8;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-question-circle"></i> Questions for: {{ $quiz->title }}
                </h5>
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
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">Order</th>
                                    <th>Question</th>
                                    <th style="width: 120px;">Type</th>
                                    <th style="width: 80px;">Points</th>
                                    <th style="width: 100px;">Time (sec)</th>
                                    <th style="width: 200px;">Options</th>
                                    <th style="width: 100px;">Actions</th>
                                 </thead>
                            <tbody id="sortable">
                                @foreach($questions as $index => $question)
                                    <tr class="question-row" data-id="{{ $question->id }}">
                                        <td class="handle text-center">
                                            <i class="fas fa-grip-vertical"></i> {{ $question->order }}
                                        </td>
                                        <td>
                                            <strong>{{ Str::limit($question->question_text, 80) }}</strong>
                                            @if($question->explanation)
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> Has explanation
                                                </small>
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
                                        <td>
                                            <span class="badge bg-success">{{ $question->points }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ $question->time_seconds }}s</span>
                                        </td>
                                        <td>
                                            @foreach($question->options as $option)
                                                <div class="small mb-1">
                                                    @if($option->is_correct)
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                        <i class="far fa-circle text-muted"></i>
                                                    @endif
                                                    {{ Str::limit($option->option_text, 40) }}
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
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger delete-question-btn" 
                                                            data-question-text="{{ Str::limit($question->question_text, 50) }}"
                                                            title="Delete">
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
                        <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Another Question
                        </a>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                        <h5>No Questions Yet</h5>
                        <p class="text-muted">This quiz doesn't have any questions. Add your first question to make the quiz available to users.</p>
                        <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Question
                        </a>
                        <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Back to Quiz
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the question:</p>
                <p class="fw-bold" id="deleteQuestionText"></p>
                <p class="text-danger">This action cannot be undone! All options for this question will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteQuestionBtn">Delete Question</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        let deleteForm = null;
        
        // Initialize Sortable for drag and drop
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
                        showNotification('Questions reordered successfully!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Error reordering:', error);
                    showNotification('Failed to reorder questions', 'danger');
                });
            }
        });
        
        // Handle delete button click
        $('.delete-question-btn').on('click', function() {
            deleteForm = $(this).closest('form');
            const questionText = $(this).data('question-text');
            $('#deleteQuestionText').text(questionText);
            $('#deleteQuestionModal').modal('show');
        });
        
        // Handle confirm delete
        $('#confirmDeleteQuestionBtn').on('click', function() {
            if (deleteForm) {
                deleteForm.submit();
            }
            $('#deleteQuestionModal').modal('hide');
        });
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '250px';
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    });
</script>
@endpush
@endsection