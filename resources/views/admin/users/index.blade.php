@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users"></i> Users Management</h5>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by name or email" 
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Users</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Users</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th width="80">Quizzes</th>
                                    <th width="80">Points</th>
                                    <th width="100">Status</th>
                                    <th width="100">Joined</th>
                                    <th width="150">Actions</th>
                                 </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($user->profile && $user->profile->profile_picture)
                                                    <img src="{{ asset('storage/' . $user->profile->profile_picture) }}" 
                                                         alt="{{ $user->name }}" 
                                                         class="rounded-circle me-2" 
                                                         style="width: 32px; height: 32px; object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 32px; height: 32px; font-size: 14px;">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none fw-bold">
                                                    {{ $user->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope text-muted me-1"></i>
                                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                        </td>
                                        <td>
                                            @if($user->profile && $user->profile->phone)
                                                <i class="fas fa-phone text-muted me-1"></i> {{ $user->profile->phone }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-chart-line"></i> {{ $user->profile->quizzes_attempted ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-star"></i> {{ $user->profile->total_points ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-ban"></i> Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <i class="far fa-calendar-alt text-muted me-1"></i>
                                            {{ $user->created_at->format('M d, Y') }}
                                            <br>
                                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.users.show', $user) }}" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $user) }}" 
                                                   class="btn btn-sm btn-primary" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.users.toggle-status', $user) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm {{ $user->is_active ? 'btn-warning' : 'btn-success' }}" 
                                                            title="{{ $user->is_active ? 'Deactivate User' : 'Activate User' }}">
                                                        <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.users.destroy', $user) }}" 
                                                      method="POST" class="d-inline delete-user-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger delete-user-btn" 
                                                            data-user-name="{{ $user->name }}"
                                                            data-user-id="{{ $user->id }}"
                                                            title="Delete User">
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
                        {{ $users->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                        <h5>No Users Found</h5>
                        <p class="text-muted">No users match your search criteria.</p>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Clear Filters
                        </a>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New User
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user: <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete User</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let deleteForm = null;
        
        // Handle delete button click
        $('.delete-user-btn').on('click', function() {
            deleteForm = $(this).closest('form');
            const userName = $(this).data('user-name');
            $('#deleteUserName').text(userName);
            $('#deleteUserModal').modal('show');
        });
        
        // Handle confirm delete
        $('#confirmDeleteBtn').on('click', function() {
            if (deleteForm) {
                deleteForm.submit();
            }
            $('#deleteUserModal').modal('hide');
        });
        
        // Initialize DataTable if needed
        if ($('#usersTable').length && $.fn.DataTable.isDataTable('#usersTable')) {
            $('#usersTable').DataTable().destroy();
        }
        
        if ($('#usersTable').length) {
            $('#usersTable').DataTable({
                pageLength: 25,
                responsive: true,
                ordering: true,
                searching: false,
                paging: false,
                info: false,
                columnDefs: [
                    { orderable: false, targets: [8] }
                ]
            });
        }
    });
</script>
@endpush
@endsection