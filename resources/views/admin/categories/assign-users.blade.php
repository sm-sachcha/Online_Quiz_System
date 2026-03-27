@extends('layouts.admin')

@section('title', 'Assign Users to Category - ' . $category->name)

@section('content')
<style>
    .user-list {
        max-height: 500px;
        overflow-y: auto;
    }
    .user-item {
        transition: all 0.3s ease;
    }
    .user-item:hover {
        background-color: #f8f9fa;
    }
    .avatar-sm {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
        padding: 12px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 10000;
    }
    .notification-success {
        background-color: #28a745;
        color: white;
    }
    .notification-error {
        background-color: #dc3545;
        color: white;
    }
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    .loading-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid #fff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
        margin-right: 5px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        text-align: center;
    }
    .action-btn {
        min-width: 85px;
        transition: all 0.3s ease;
    }
    .action-btn i {
        margin-right: 5px;
    }
    .badge-status {
        font-size: 12px;
        padding: 5px 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .assigned-badge {
        background-color: #28a745;
        color: white;
    }
    .not-assigned-badge {
        background-color: #6c757d;
        color: white;
    }
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-success:hover {
        background-color: #218838;
    }
    .btn-danger:hover {
        background-color: #c82333;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Assign Users to Category: {{ $category->name }}</h5>
            </div>
            <div class="card-body">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h3 id="assignedCount">{{ $assignedUsers->count() }}</h3>
                            <p class="mb-0">Assigned Users</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #0fa074 100%);">
                            <h3>{{ $users->count() }}</h3>
                            <p class="mb-0">Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                            <h3>{{ $category->quizzes()->count() }}</h3>
                            <p class="mb-0">Quizzes in Category</p>
                        </div>
                    </div>
                </div>

                <!-- Category Info -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Category Details:</strong> {{ $category->name }}
                    @if($category->description)
                        <br><strong>Description:</strong> {{ $category->description }}
                    @endif
                    <br><strong>Created By:</strong> {{ $category->creator->name }}
                </div>

                <!-- Search Users -->
                <div class="mb-3">
                    <label class="form-label">Search Users</label>
                    <input type="text" id="searchUsers" class="form-control" placeholder="Search by name or email...">
                </div>

                <!-- Users List -->
                <div class="user-list">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th width="120">Status</th>
                                <th width="120">Actions</th>
                             </thead>
                        <tbody>
                            @foreach($users as $index => $user)
                                @php
                                    $isAssigned = $assignedUsers->contains($user->id);
                                @endphp
                                <tr class="user-item" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}" data-is-assigned="{{ $isAssigned ? 'true' : 'false' }}">
                                    <td class="text-center">{{ $index + 1 }}    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <strong>{{ $user->name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($isAssigned)
                                            <span class="badge assigned-badge status-badge" id="status-badge-{{ $user->id }}">
                                                <i class="fas fa-check-circle"></i> Assigned
                                            </span>
                                        @else
                                            <span class="badge not-assigned-badge status-badge" id="status-badge-{{ $user->id }}">
                                                <i class="fas fa-times-circle"></i> Not Assigned
                                            </span>
                                        @endif
                                    </td>
                                    <td class="action-cell">
                                        @if($isAssigned)
                                            <button type="button" class="btn btn-sm btn-danger action-btn" 
                                                    data-action="remove" 
                                                    data-user-id="{{ $user->id }}" 
                                                    data-user-name="{{ $user->name }}">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-success action-btn" 
                                                    data-action="assign" 
                                                    data-user-id="{{ $user->id }}" 
                                                    data-user-name="{{ $user->name }}">
                                                <i class="fas fa-plus"></i> Assign
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Categories
                    </a>
                    <button type="button" class="btn btn-primary" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Show notification function
        function showNotification(message, type = 'success') {
            const bgColor = type === 'success' ? '#10882c' : '#dc3545';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            // Remove existing notifications
            $('.notification').remove();
            
            const notification = $(`
                <div class="notification" style="background: ${bgColor}; color: white;">
                    <i class="fas ${icon}"></i>
                    <span>${message}</span>
                    <button style="background: none; border: none; color: white; margin-left: auto; cursor: pointer; font-size: 18px;">&times;</button>
                </div>
            `);
            $('body').append(notification);
            
            // Close button handler
            notification.find('button').on('click', function() {
                notification.remove();
            });
            
            setTimeout(() => {
                notification.fadeOut(300, function() { $(this).remove(); });
            }, 3000);
        }
        
        // Show loading on button
        function showLoading(button) {
            const originalHtml = button.html();
            button.data('original-html', originalHtml);
            button.html('<span class="loading-spinner"></span>');
            button.prop('disabled', true);
        }
        
        function hideLoading(button) {
            const originalHtml = button.data('original-html');
            button.html(originalHtml);
            button.prop('disabled', false);
        }
        
        // Update assigned count
        function updateAssignedCount() {
            const assignedCount = $('.assigned-badge').length;
            $('#assignedCount').text(assignedCount);
            console.log('Assigned count updated:', assignedCount);
        }
        
        // Handle action button click
        function handleActionClick() {
            const button = $(this);
            const userId = button.data('user-id');
            const userName = button.data('user-name');
            const action = button.data('action');
            const row = button.closest('tr');
            const statusBadge = row.find('.status-badge');
            const actionCell = row.find('.action-cell');
            
            console.log('Action:', action, 'User:', userName, 'ID:', userId);
            
            showLoading(button);
            
            $.ajax({
                url: '{{ route("admin.categories.assign-user", $category) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_id: userId,
                    action: action
                },
                success: function(response) {
                    console.log('Response:', response);
                    
                    if (response.success) {
                        if (action === 'assign') {
                            // Change button to Remove
                            actionCell.html(`
                                <button type="button" class="btn btn-sm btn-danger action-btn" 
                                        data-action="remove" 
                                        data-user-id="${userId}" 
                                        data-user-name="${userName}">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            `);
                            
                            // Change status badge
                            statusBadge.removeClass('not-assigned-badge');
                            statusBadge.addClass('assigned-badge');
                            statusBadge.html('<i class="fas fa-check-circle"></i> Assigned');
                            
                            // Update row data attribute
                            row.data('is-assigned', 'true');
                            
                            showNotification(`User "${userName}" has been assigned to this category.`, 'success');
                        } else {
                            // Change button to Assign
                            actionCell.html(`
                                <button type="button" class="btn btn-sm btn-success action-btn" 
                                        data-action="assign" 
                                        data-user-id="${userId}" 
                                        data-user-name="${userName}">
                                    <i class="fas fa-plus"></i> Assign
                                </button>
                            `);
                            
                            // Change status badge
                            statusBadge.removeClass('assigned-badge');
                            statusBadge.addClass('not-assigned-badge');
                            statusBadge.html('<i class="fas fa-times-circle"></i> Not Assigned');
                            
                            // Update row data attribute
                            row.data('is-assigned', 'false');
                            
                            showNotification(`User "${userName}" has been removed from this category.`, 'success');
                        }
                        
                        // Re-attach event handler to the new button
                        actionCell.find('.action-btn').on('click', handleActionClick);
                        
                        // Update assigned count
                        updateAssignedCount();
                    } else {
                        showNotification(`Error: ${response.message}`, 'danger');
                        hideLoading(button);
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    let errorMsg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showNotification(`Error: ${errorMsg}`, 'danger');
                    hideLoading(button);
                }
            });
        }
        
        // Attach event handlers to action buttons
        $('.action-btn').on('click', handleActionClick);
        
        // Search functionality
        $('#searchUsers').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#usersTable tbody tr').each(function() {
                const userName = $(this).data('user-name').toLowerCase();
                const userEmail = $(this).data('user-email').toLowerCase();
                if (userName.indexOf(searchTerm) > -1 || userEmail.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Refresh button
        $('#refreshBtn').on('click', function() {
            location.reload();
        });
        
        // Initialize assigned count
        updateAssignedCount();
        
        console.log('Page loaded. Assigned users:', $('.assigned-badge').length);
    });
</script>
@endpush
@endsection