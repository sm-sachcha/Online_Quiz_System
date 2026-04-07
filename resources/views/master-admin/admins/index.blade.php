@extends('layouts.admin')

@section('title', 'Manage Administrators')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-tie"></i> Administrators Management</h5>
                <div>
                    <button type="button" class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#promoteUserModal">
                        <i class="fas fa-arrow-up"></i> Promote User to Admin
                    </button>
                    <a href="{{ route('master-admin.admins.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add New Administrator
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Info Alert -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Administrator Roles:</strong> 
                    <span class="badge bg-danger ms-2">Master Admin</span> - Full system access including managing other admins
                    <span class="badge bg-info ms-2">General Admin</span> - Can manage quizzes, categories, questions, and view reports
                </div>
                
                <!-- Search Form -->
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
                            <select name="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>General Admin</option>
                                <option value="master_admin" {{ request('role') == 'master_admin' ? 'selected' : '' }}>Master Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('master-admin.admins.index') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                @if($admins->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover" id="adminsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Quizzes</th>
                                    <th>Categories</th>
                                    <th>Questions</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th width="220">Actions</th>
                                 </thead>
                            <tbody>
                                @foreach($admins as $admin)
                                    <tr class="{{ $admin->role == 'master_admin' ? 'table-danger' : '' }}">
                                        <td>{{ $admin->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 32px; height: 32px;">
                                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $admin->name }}</strong>
                                                    @if($admin->id == Auth::id())
                                                        <br><small class="text-muted">(You)</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td><i class="fas fa-envelope text-muted"></i> {{ $admin->email }}</td>
                                        <td>
                                            @if($admin->role == 'master_admin')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-crown"></i> Master Admin
                                                </span>
                                            @else
                                                <span class="badge bg-info">
                                                    <i class="fas fa-user-shield"></i> General Admin
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $admin->created_quizzes_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $admin->created_categories_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $admin->created_questions_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            @if($admin->is_active)
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
                                            <i class="far fa-calendar-alt"></i> {{ $admin->created_at->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('master-admin.admins.show', $admin) }}" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($admin->id !== Auth::id())
                                                    <a href="{{ route('master-admin.admins.edit', $admin) }}" 
                                                       class="btn btn-sm btn-primary" title="Edit Admin">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <form action="{{ route('master-admin.admins.toggle-status', $admin) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-sm {{ $admin->is_active ? 'btn-warning' : 'btn-success' }}" 
                                                                title="{{ $admin->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="fas {{ $admin->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    @if($admin->role == 'admin')
                                                        <form action="{{ route('master-admin.admins.promote') }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ $admin->id }}">
                                                            <input type="hidden" name="role" value="master_admin">
                                                            <button type="submit" 
                                                                    class="btn btn-sm btn-success" 
                                                                    title="Promote to Master Admin">
                                                                <i class="fas fa-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                    @elseif($admin->role == 'master_admin' && $admin->id !== Auth::id())
                                                        <form action="{{ route('master-admin.admins.demote', $admin) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" 
                                                                    class="btn btn-sm btn-secondary" 
                                                                    title="Demote to General Admin">
                                                                <i class="fas fa-arrow-down"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    <form action="{{ route('master-admin.admins.destroy', $admin) }}" 
                                                          method="POST" class="d-inline delete-admin-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger delete-admin-btn" 
                                                                data-admin-name="{{ $admin->name }}"
                                                                data-admin-id="{{ $admin->id }}"
                                                                title="Delete Admin">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $admins->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                        <h5>No Administrators Found</h5>
                        <p class="text-muted">Click the button above to add your first administrator.</p>
                        <div class="mt-3">
                            <a href="{{ route('master-admin.admins.create') }}" class="btn btn-primary me-2">
                                <i class="fas fa-plus"></i> Add New Administrator
                            </a>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#promoteUserModal">
                                <i class="fas fa-arrow-up"></i> Promote User to Admin
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Promote User Modal -->
<div class="modal fade" id="promoteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-arrow-up"></i> Promote User to Administrator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('master-admin.admins.promote') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Select a user to promote...</option>
                            @php
                                $regularUsers = \App\Models\User::where('role', 'user')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get();
                            @endphp
                            @if($regularUsers->count() > 0)
                                @foreach($regularUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            @else
                                <option value="" disabled>No regular users available to promote</option>
                            @endif
                        </select>
                        @if($regularUsers->count() == 0)
                            <small class="text-danger">No regular users found. Users must register first before they can be promoted.</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role to Assign</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="admin">General Admin</option>
                            <option value="master_admin">Master Admin</option>
                        </select>
                        <div class="mt-2">
                            <small class="text-muted d-block">
                                <i class="fas fa-info-circle text-info"></i> 
                                <strong>General Admin:</strong> Can manage quizzes, categories, and view reports.
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-info-circle text-danger"></i> 
                                <strong>Master Admin:</strong> Full system access including managing other admins.
                            </small>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        The selected user will be promoted to the chosen role. They will retain their existing profile and data.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" {{ $regularUsers->count() == 0 ? 'disabled' : '' }}>
                        <i class="fas fa-arrow-up"></i> Promote User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Administrator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete administrator: <strong id="deleteAdminName"></strong>?</p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAdminBtn">Delete Administrator</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let deleteForm = null;
        
        // Handle delete button click - show modal instead of confirm
        $('.delete-admin-btn').on('click', function() {
            deleteForm = $(this).closest('form');
            const adminName = $(this).data('admin-name');
            $('#deleteAdminName').text(adminName);
            $('#deleteAdminModal').modal('show');
        });
        
        // Handle confirm delete from modal
        $('#confirmDeleteAdminBtn').on('click', function() {
            if (deleteForm) {
                deleteForm.submit();
            }
            $('#deleteAdminModal').modal('hide');
        });
        
        // Initialize DataTable
        if ($('#adminsTable').length && $.fn.DataTable.isDataTable('#adminsTable')) {
            $('#adminsTable').DataTable().destroy();
        }
        
        if ($('#adminsTable').length) {
            $('#adminsTable').DataTable({
                pageLength: 25,
                responsive: true,
                ordering: true,
                searching: false,
                paging: false,
                info: false,
                columnDefs: [
                    { orderable: false, targets: [9] }
                ]
            });
        }
        
        // Handle promote/demote confirmations
        $('form[action*="promote"]').on('submit', function(e) {
            if (!confirm('Are you sure you want to promote this user? This will give them admin privileges.')) {
                e.preventDefault();
                return false;
            }
        });
        
        $('form[action*="demote"]').on('submit', function(e) {
            if (!confirm('Are you sure you want to demote this admin? They will lose admin privileges.')) {
                e.preventDefault();
                return false;
            }
        });
        
        $('form[action*="toggle-status"]').on('submit', function(e) {
            const action = $(this).find('button').hasClass('btn-warning') ? 'deactivate' : 'activate';
            if (!confirm(`Are you sure you want to ${action} this administrator?`)) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
@endpush
@endsection