@extends('layouts.admin')

@section('title', 'Manage Categories')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tags"></i> Categories</h5>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New Category
                </a>
            </div>
            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Quizzes</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                 </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td>
                                            @if($category->icon)
                                                <i class="{{ $category->icon }}" style="color: {{ $category->color }}; font-size: 20px;"></i>
                                            @else
                                                <i class="fas fa-tag" style="color: {{ $category->color }}; font-size: 20px;"></i>
                                            @endif
                                        </td>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td>{{ Str::limit($category->description, 50) }}</td>
                                        <td><span class="badge bg-info">{{ $category->quizzes_count }}</span></td>
                                        <td>
                                            @if($category->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $category->creator->name }}</td>
                                        <td>{{ $category->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.categories.edit', $category) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.categories.destroy', $category) }}" 
                                                  method="POST" class="d-inline delete-category-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-category-btn" 
                                                        data-category-name="{{ $category->name }}"
                                                        data-category-id="{{ $category->id }}"
                                                        title="Delete Category">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                     </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $categories->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <h5>No Categories Found</h5>
                        <p class="text-muted">Click the button above to create your first category.</p>
                        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Category
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete category: <strong id="deleteCategoryName"></strong>?</p>
                <p class="text-danger">This action cannot be undone. If there are quizzes in this category, they will become uncategorized.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCategoryBtn">Delete Category</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let deleteCategoryForm = null;
        
        // Handle delete button click
        $('.delete-category-btn').on('click', function() {
            deleteCategoryForm = $(this).closest('form');
            const categoryName = $(this).data('category-name');
            $('#deleteCategoryName').text(categoryName);
            $('#deleteCategoryModal').modal('show');
        });
        
        // Handle confirm delete
        $('#confirmDeleteCategoryBtn').on('click', function() {
            if (deleteCategoryForm) {
                deleteCategoryForm.submit();
            }
            $('#deleteCategoryModal').modal('hide');
        });
        
        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#categoriesTable')) {
            $('#categoriesTable').DataTable().destroy();
        }
        
        $('#categoriesTable').DataTable({
            pageLength: 25,
            responsive: true,
            ordering: true,
            searching: true,
            paging: false,
            info: false,
            columnDefs: [
                { orderable: false, targets: [1, 8] }
            ]
        });
    });
</script>
@endpush
@endsection