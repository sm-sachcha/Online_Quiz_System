@extends('layouts.admin')

@section('title', 'Manage Questions - ' . $quiz->title)

@section('content')
<style>
    .handle {
        cursor: grab;
        color: #6c757d;
        transition: color 0.3s;
    }
    .handle:active {
        cursor: grabbing;
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
        cursor: grabbing !important;
    }
    .sortable-chosen {
        background-color: #fff3cd;
    }
    .reorder-loading {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 9999;
        display: none;
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
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="questionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">Order</th>
                                    <th>Question</th>
                                    <th style="width: 130px;">Type</th>
                                    <th style="width: 80px;">Points</th>
                                    <th style="width: 100px;">Time (sec)</th>
                                    <th style="width: 220px;">Options</th>
                                    <th style="width: 100px;">Actions</th>
                                 </thead>
                            <tbody id="sortable">
                                @foreach($questions as $index => $question)
                                    <tr class="question-row" data-id="{{ $question->id }}" data-order="{{ $question->order }}">
                                        <td class="handle text-center" style="cursor: grab;">
                                            <i class="fas fa-grip-vertical"></i> <span class="order-badge">{{ $question->order }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ Str::limit($question->question_text, 80) }}</strong>
                                            @if($question->explanation)
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> Has explanation
                                                </small>
                                            @endif
                                            @if($question->show_answer)
                                                <br>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-eye"></i> Show Answer
                                                </span>
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
                                            @foreach($question->options as $optIndex => $option)
                                                <div class="small mb-1">
                                                    @if($option->is_correct)
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                        <i class="far fa-circle text-muted"></i>
                                                    @endif
                                                    <span class="option-text">{{ Str::limit($option->option_text, 35) }}</span>
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
                                                      method="POST" class="d-inline delete-form" id="delete-form-{{ $question->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger delete-question-btn" 
                                                            data-id="{{ $question->id }}"
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the question:</p>
                <p class="fw-bold text-danger" id="deleteQuestionText"></p>
                <p class="text-warning">This action cannot be undone! All options for this question will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteQuestionBtn">Delete Question</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="reorder-loading" id="reorderLoading">
    <i class="fas fa-spinner fa-spin"></i> Saving order...
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        let deleteId = null;
        let isReordering = false;
        
        // Check if Sortable is loaded
        if (typeof Sortable === 'undefined') {
            console.error('SortableJS not loaded!');
            alert('Drag and drop library not loaded. Please refresh the page.');
        } else {
            console.log('SortableJS loaded successfully');
        }
        
        // Initialize Sortable for drag and drop
        const sortableContainer = document.getElementById('sortable');
        if (sortableContainer) {
            const sortable = new Sortable(sortableContainer, {
                handle: '.handle',
                animation: 300,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                chosenClass: 'sortable-chosen',
                onStart: function() {
                    console.log('Drag started');
                },
                onEnd: function(evt) {
                    console.log('Drag ended');
                    if (isReordering) return;
                    
                    const questions = [];
                    const rows = document.querySelectorAll('#sortable tr');
                    
                    rows.forEach((row, index) => {
                        const newOrder = index + 1;
                        const questionId = row.dataset.id;
                        const orderSpan = row.querySelector('.order-badge');
                        
                        if (orderSpan) {
                            orderSpan.textContent = newOrder;
                        }
                        
                        questions.push({
                            id: questionId,
                            order: newOrder
                        });
                    });
                    
                    console.log('Sending reorder request:', questions);
                    
                    // Show loading
                    $('#reorderLoading').fadeIn();
                    isReordering = true;
                    
                    // Send AJAX request
                    fetch('{{ route("admin.quizzes.questions.reorder", $quiz) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ questions: questions })
                    })
                    .then(response => response.json())
                    .then(data => {
                        $('#reorderLoading').fadeOut();
                        isReordering = false;
                        
                        if (data.success) {
                            showNotification('Questions reordered successfully!', 'success');
                            // Update order numbers in the table
                            rows.forEach((row, idx) => {
                                const handleSpan = row.querySelector('.handle');
                                if (handleSpan) {
                                    handleSpan.innerHTML = '<i class="fas fa-grip-vertical"></i> <span class="order-badge">' + (idx + 1) + '</span>';
                                }
                            });
                        } else {
                            showNotification('Failed to reorder questions: ' + (data.message || 'Unknown error'), 'danger');
                            // Reload to restore original order
                            setTimeout(() => location.reload(), 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error reordering:', error);
                        $('#reorderLoading').fadeOut();
                        isReordering = false;
                        showNotification('Network error. Please refresh the page.', 'danger');
                    });
                }
            });
            
            console.log('Sortable initialized successfully');
        } else {
            console.error('Sortable container not found');
        }
        
        // Handle delete button click
        $('.delete-question-btn').on('click', function() {
            deleteId = $(this).data('id');
            const questionText = $(this).data('question-text');
            $('#deleteQuestionText').text(questionText);
            $('#deleteQuestionModal').modal('show');
        });
        
        // Handle confirm delete
        $('#confirmDeleteQuestionBtn').on('click', function() {
            if (deleteId) {
                $(`#delete-form-${deleteId}`).submit();
            }
            $('#deleteQuestionModal').modal('hide');
        });
        
        function showNotification(message, type) {
            const notification = $('<div class="alert alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; min-width: 300px;">' +
                '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' me-2"></i>' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            
            if (type === 'success') {
                notification.addClass('alert-success');
            } else if (type === 'danger') {
                notification.addClass('alert-danger');
            } else {
                notification.addClass('alert-info');
            }
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // Debug: Log when page loads
        console.log('Questions page loaded', {
            quizId: {{ $quiz->id }},
            questionsCount: {{ $questions->count() }},
            reorderUrl: '{{ route("admin.quizzes.questions.reorder", $quiz) }}'
        });
    });
</script>
@endpush
@endsection