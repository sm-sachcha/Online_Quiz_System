@extends('layouts.admin')

@section('title', 'Manage Quizzes')

@section('content')
<style>
    .share-link-container {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .share-link-input {
        font-size: 12px;
        padding: 4px 8px;
        width: 200px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background-color: #f8f9fa;
    }
    .copy-link-btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    .copy-success {
        background-color: #28a745;
        color: white;
        border-color: #28a745;
    }
    .quiz-link-modal {
        cursor: pointer;
    }
    .quiz-link-modal:hover {
        opacity: 0.8;
    }
</style>

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
                                    <th class="text-center">Title</th>
                                    <!-- <th>Category</th> -->
                                    <th class="text-center">Questions</th>
                                    <th class="text-center">Total Time</th>
                                    <th class="text-center">Points</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Share Link</th>
                                    <th class="text-center">Actions</th>
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
                                        <!-- <td>
                                            @if($quiz->category)
                                                <span class="badge" style="background-color: {{ $quiz->category->color ?? '#6c757d' }}">
                                                    <i class="{{ $quiz->category->icon ?? 'fas fa-tag' }}"></i>
                                                    {{ $quiz->category->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-folder-open"></i> No Category
                                                </span>
                                            @endif
                                        </td> -->
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
                                            <div class="share-link-container">
                                                <input type="text" class="share-link-input" id="quizLink{{ $quiz->id }}" 
                                                       value="{{ url('/user/quiz/lobby/' . $quiz->id) }}" readonly>
                                                <button class="btn btn-sm btn-primary copy-link-btn" data-quiz-id="{{ $quiz->id }}">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1 overflow-auto">

                                                <a href="{{ route('admin.quizzes.show', $quiz) }}" 
                                                class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" 
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" 
                                                class="btn btn-sm btn-outline-secondary" title="Questions">
                                                    <i class="fas fa-question-circle"></i>
                                                </a>

                                                <a href="{{ route('admin.quizzes.participants', $quiz) }}" 
                                                class="btn btn-sm btn-outline-success" title="Participants">
                                                    <i class="fas fa-users"></i>
                                                </a>

                                                <form action="{{ route('admin.quizzes.duplicate', $quiz) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Duplicate">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </form>

                                                <form action="{{ route('admin.quizzes.toggle-publish', $quiz) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm {{ $quiz->is_published ? 'btn-success' : 'btn-outline-success' }}" 
                                                            title="{{ $quiz->is_published ? 'Unpublish Quiz' : 'Publish Quiz' }}">
                                                        
                                                        <i class="fas {{ $quiz->is_published ? 'fa-unlock' : 'fa-lock' }}"></i>
                                                        
                                                    </button>
                                                </form>

                                                <form action="{{ route('admin.quizzes.destroy', $quiz) }}" 
                                                    method="POST" class="delete-quiz-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger delete-quiz-btn" 
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

<!-- Share Quiz Modal -->
<div class="modal fade" id="shareQuizModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-share-alt"></i> Share Quiz Link</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Share this link with participants to join the quiz:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="modalQuizLink" readonly>
                    <button class="btn btn-primary" id="modalCopyLinkBtn">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> Participants can join the quiz even if they don't have an account. They just need to enter their name.
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Important:</strong> The quiz must be published for participants to access it.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        
        // Handle copy link for each quiz
        $('.copy-link-btn').on('click', function() {
            const quizId = $(this).data('quiz-id');
            const linkInput = document.getElementById(`quizLink${quizId}`);
            
            if (linkInput) {
                linkInput.select();
                linkInput.setSelectionRange(0, 99999);
                document.execCommand('copy');
                
                // Show success feedback
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="fas fa-check"></i> Copied!');
                btn.addClass('copy-success');
                
                setTimeout(() => {
                    btn.html(originalHtml);
                    btn.removeClass('copy-success');
                }, 2000);
                
                // Show toast notification
                showToast('Link copied to clipboard!', 'success');
            }
        });
        
        // Handle share modal (if needed)
        $('.quiz-link-modal').on('click', function() {
            const quizLink = $(this).data('quiz-link');
            $('#modalQuizLink').val(quizLink);
            $('#shareQuizModal').modal('show');
        });
        
        // Handle modal copy link
        $('#modalCopyLinkBtn').on('click', function() {
            const linkInput = document.getElementById('modalQuizLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            $(this).html('<i class="fas fa-check"></i> Copied!');
            setTimeout(() => {
                $(this).html('<i class="fas fa-copy"></i> Copy Link');
            }, 2000);
            
            showToast('Link copied to clipboard!', 'success');
        });
        
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = $(`
                <div class="toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `);
            
            $('body').append(toast);
            const bsToast = new bootstrap.Toast(toast[0], { delay: 3000 });
            bsToast.show();
            
            toast.on('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
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
        
        // Auto-select link on click
        $('.share-link-input').on('click', function() {
            this.select();
            this.setSelectionRange(0, 99999);
        });
    });
</script>
@endpush
@endsection