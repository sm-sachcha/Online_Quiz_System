@extends('layouts.admin')

@section('title', 'Manage Quizzes')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Quizzes Management</h5>
                <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Create New Quiz
                </a>
            </div>
            <div class="card-body">
                @if($quizzes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Questions</th>
                                    <th>Total Time</th>
                                    <th>Points</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                 </thead>
                            <tbody>
                                @foreach($quizzes as $quiz)
                                    <tr>
                                        <td>{{ $quiz->id }}</td>
                                        <td>
                                            <strong>{{ $quiz->title }}</strong>
                                            <br>
                                            <small class="text-muted">Attempts: {{ $quiz->attempts_count }}</small>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $quiz->category->color ?? '#6c757d' }}">
                                                <i class="{{ $quiz->category->icon ?? 'fas fa-tag' }}"></i>
                                                {{ $quiz->category->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $quiz->questions_count }} questions</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="far fa-clock"></i> {{ $quiz->duration_minutes }} minutes
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $quiz->total_points }} pts</span>
                                        </td>
                                        <td>
                                            @if($quiz->is_published)
                                                @if($quiz->ends_at && $quiz->ends_at < now())
                                                    <span class="badge bg-secondary">Expired</span>
                                                @elseif($quiz->scheduled_at && $quiz->scheduled_at > now())
                                                    <span class="badge bg-warning">Scheduled</span>
                                                @else
                                                    <span class="badge bg-success">Published</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">Hidden</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.quizzes.show', $quiz) }}" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" 
                                                   class="btn btn-sm btn-secondary" title="Manage Questions">
                                                    <i class="fas fa-question-circle"></i>
                                                </a>
                                                <form action="{{ route('admin.quizzes.duplicate', $quiz) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Duplicate">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.quizzes.toggle-publish', $quiz) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $quiz->is_published ? 'btn-warning' : 'btn-success' }}" 
                                                            title="{{ $quiz->is_published ? 'Hide Quiz' : 'Publish Quiz' }}">
                                                        <i class="fas {{ $quiz->is_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.quizzes.destroy', $quiz) }}" 
                                                      method="POST" class="d-inline delete-quiz-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger delete-quiz-btn" 
                                                            data-quiz-title="{{ $quiz->title }}"
                                                            data-quiz-id="{{ $quiz->id }}"
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
                        {{ $quizzes->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                        <h5>No Quizzes Found</h5>
                        <p class="text-muted">Create your first quiz to get started!</p>
                        <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Quiz
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteQuizModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete quiz: <strong id="deleteQuizTitle"></strong>?</p>
                <p class="text-danger">This will also delete all questions and attempts associated with this quiz. This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteQuizBtn">Delete Quiz</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let deleteQuizForm = null;
        
        // Handle delete button click
        $('.delete-quiz-btn').on('click', function() {
            deleteQuizForm = $(this).closest('form');
            const quizTitle = $(this).data('quiz-title');
            $('#deleteQuizTitle').text(quizTitle);
            $('#deleteQuizModal').modal('show');
        });
        
        // Handle confirm delete
        $('#confirmDeleteQuizBtn').on('click', function() {
            if (deleteQuizForm) {
                deleteQuizForm.submit();
            }
            $('#deleteQuizModal').modal('hide');
        });
    });
</script>
@endpush
@endsection