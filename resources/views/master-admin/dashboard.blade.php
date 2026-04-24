@extends('layouts.admin')

@section('title', 'Master Admin Dashboard')

@section('content')
<style>
    .stat-card {
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .quick-action-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        border-radius: 12px;
    }
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .activity-item {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    .activity-item:hover {
        background-color: #f8f9fa;
        border-left-color: #007bff;
        transform: translateX(5px);
    }
    .stats-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(255,255,255,0.2);
    }
    .system-status {
        padding: 10px;
        border-radius: 8px;
    }
    .status-active {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    .status-maintenance {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }
    .avatar-sm {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
    .see-more-btn {
        background-color: #6c757d;
        color: white;
        transition: all 0.3s ease;
        border: none;
        padding: 8px 20px;
        border-radius: 25px;
    }
    .see-more-btn:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }
    .activity-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .activity-list .list-group-item {
        transition: all 0.3s ease;
    }
    .load-more-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #fff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
        margin-right: 8px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .activity-time {
        font-size: 11px;
        white-space: nowrap;
    }
    /* Equal Height Cards */
    .equal-height-card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .equal-height-card .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .quick-actions-grid {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .quick-actions-row {
        flex: 1;
    }
    .activity-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 450px;
    }
    .activity-list-wrapper {
        flex: 1;
        overflow-y: auto;
    }
    @media (max-width: 768px) {
        .activity-time {
            white-space: normal;
        }
        .avatar-sm {
            width: 35px;
            height: 35px;
            font-size: 14px;
        }
        .equal-height-card {
            margin-bottom: 20px;
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Welcome message - ONLY shows on login -->
        @if(session('show_welcome') && session('welcome_message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-crown"></i> {{ session('welcome_message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @php
                session()->forget('show_welcome');
                session()->forget('welcome_message');
            @endphp
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Master Admin Dashboard</h2>
        <p class="text-muted">Welcome back, <strong>{{ Auth::user()->name }}</strong>! You have full system access.</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4 stats-cards">
    <div class="col-md-3">
        <div class="card text-white bg-primary stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Admins</h6>
                        <h2 class="mb-0">{{ \App\Models\User::whereIn('role', ['admin', 'master_admin'])->count() }}</h2>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="opacity-75">
                        <i class="fas fa-users"></i> 
                        {{ \App\Models\User::where('role', 'admin')->count() }} General Admins
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mb-0">{{ \App\Models\User::where('role', 'user')->count() }}</h2>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="opacity-75">
                        <i class="fas fa-user-plus"></i> 
                        +{{ \App\Models\User::where('role', 'user')->where('created_at', '>=', now()->subDays(7))->count() }} this week
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-info stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Quizzes</h6>
                        <h2 class="mb-0">{{ \App\Models\Quiz::count() }}</h2>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-question-circle fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="opacity-75">
                        <i class="fas fa-check-circle"></i> 
                        {{ \App\Models\Quiz::where('is_published', true)->count() }} Published
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-warning stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">System Status</h6>
                        <h2 class="mb-0">
                            @if(app()->isDownForMaintenance())
                                <i class="fas fa-exclamation-triangle"></i> Maintenance
                            @else
                                <i class="fas fa-check-circle"></i> Active
                            @endif
                        </h2>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-server fa-2x"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="opacity-75">
                        <i class="fas fa-clock"></i> 
                        Uptime: {{ \Carbon\Carbon::parse(exec('uptime -s'))->diffForHumans() ?? 'N/A' }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Activity - Equal Height Cards -->
<div class="row mt-4">
    <!-- Quick Actions Card -->
    <div class="col-md-6">
        <div class="card shadow-sm equal-height-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <div class="row quick-actions-row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('master-admin.admins.create') }}" class="text-decoration-none">
                                <div class="quick-action-card card bg-success text-white">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-user-plus fa-3x mb-2"></i>
                                        <h6 class="mb-0">Add New Administrator</h6>
                                        <small class="opacity-75">Create admin or master admin</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('master-admin.admins.index') }}" class="text-decoration-none">
                                <div class="quick-action-card card bg-info text-white">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-user-tie fa-3x mb-2"></i>
                                        <h6 class="mb-0">Manage Administrators</h6>
                                        <small class="opacity-75">View, edit, or delete admins</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('master-admin.settings.index') }}" class="text-decoration-none">
                                <div class="quick-action-card card bg-secondary text-white">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-cog fa-3x mb-2"></i>
                                        <h6 class="mb-0">System Settings</h6>
                                        <small class="opacity-75">Configure application settings</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('master-admin.settings.maintenance') }}" class="text-decoration-none">
                                <div class="quick-action-card card bg-warning text-dark">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-tools fa-3x mb-2"></i>
                                        <h6 class="mb-0">Maintenance Mode</h6>
                                        <small class="opacity-75">Toggle site maintenance</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('master-admin.settings.cache') }}" class="text-decoration-none">
                                <div class="quick-action-card card bg-dark text-white">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-database fa-3x mb-2"></i>
                                        <h6 class="mb-0">Cache Management</h6>
                                        <small class="opacity-75">Clear application cache</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('master-admin.settings.logs') }}" class="text-decoration-none">
                                <div class="quick-action-card card bg-danger text-white">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-file-alt fa-3x mb-2"></i>
                                        <h6 class="mb-0">System Logs</h6>
                                        <small class="opacity-75">View application logs</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Admin Activity Card -->
    <div class="col-md-6">
        <div class="card shadow-sm equal-height-card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Recent Admin Activity</h5>
                <span class="badge bg-light text-dark" id="totalActivityCount">0</span>
            </div>
            <div class="card-body activity-container">
                @php
                    $allActivities = \App\Models\UserActivity::with('user')
                        ->whereHas('user', function($q) {
                            $q->whereIn('role', ['admin', 'master_admin']);
                        })
                        ->latest()
                        ->get();
                    
                    $initialActivities = $allActivities->take(5);
                    $remainingActivities = $allActivities->slice(5);
                @endphp
                
                <div class="activity-list-wrapper">
                    <div id="activityList" class="activity-list">
                        @if($initialActivities->count() > 0)
                            <div class="list-group list-group-flush" id="initialActivities">
                                @foreach($initialActivities as $activity)
                                    <div class="list-group-item activity-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-3">
                                                    {{ strtoupper(substr($activity->user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $activity->user->name }}</strong>
                                                    @if($activity->user->role == 'master_admin')
                                                        <span class="badge bg-danger ms-1">Master</span>
                                                    @else
                                                        <span class="badge bg-info ms-1">Admin</span>
                                                    @endif
                                                    <br>
                                                    <small class="text-muted">
                                                        @if($activity->action == 'login')
                                                            <i class="fas fa-sign-in-alt text-success"></i> Logged in
                                                        @elseif($activity->action == 'logout')
                                                            <i class="fas fa-sign-out-alt text-secondary"></i> Logged out
                                                        @else
                                                            <i class="fas fa-edit text-info"></i> {{ ucfirst($activity->action) }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <small class="text-muted activity-time" title="{{ $activity->created_at }}">
                                                <i class="far fa-clock"></i> {{ $activity->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> IP: {{ $activity->ip_address }}
                                                @if($activity->user_agent)
                                                    <br><i class="fas fa-globe"></i> {{ Str::limit($activity->user_agent, 50) }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @if($remainingActivities->count() > 0)
                                <div id="moreActivities" style="display: none;">
                                    <div class="list-group list-group-flush">
                                        @foreach($remainingActivities as $activity)
                                            <div class="list-group-item activity-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-3">
                                                            {{ strtoupper(substr($activity->user->name, 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <strong>{{ $activity->user->name }}</strong>
                                                            @if($activity->user->role == 'master_admin')
                                                                <span class="badge bg-danger ms-1">Master</span>
                                                            @else
                                                                <span class="badge bg-info ms-1">Admin</span>
                                                            @endif
                                                            <br>
                                                            <small class="text-muted">
                                                                @if($activity->action == 'login')
                                                                    <i class="fas fa-sign-in-alt text-success"></i> Logged in
                                                                @elseif($activity->action == 'logout')
                                                                    <i class="fas fa-sign-out-alt text-secondary"></i> Logged out
                                                                @else
                                                                    <i class="fas fa-edit text-info"></i> {{ ucfirst($activity->action) }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted activity-time" title="{{ $activity->created_at }}">
                                                        <i class="far fa-clock"></i> {{ $activity->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt"></i> IP: {{ $activity->ip_address }}
                                                        @if($activity->user_agent)
                                                            <br><i class="fas fa-globe"></i> {{ Str::limit($activity->user_agent, 50) }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3 pt-2" id="seeMoreContainer">
                                    <button class="see-more-btn" id="seeMoreActivitiesBtn">
                                        <i class="fas fa-chevron-down"></i> See More ({{ $remainingActivities->count() }} more)
                                    </button>
                                    <button class="see-more-btn" id="showLessActivitiesBtn" style="display: none;">
                                        <i class="fas fa-chevron-up"></i> Show Less
                                    </button>
                                </div>
                            @endif
                            
                            <script>
                                document.getElementById('totalActivityCount').textContent = '{{ $allActivities->count() }}';
                            </script>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activities.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3 text-center">
                            <i class="fas fa-code-branch fa-2x text-primary mb-2"></i>
                            <h6 class="mb-0">Laravel Version</h6>
                            <p class="text-muted mb-0">{{ app()->version() }}</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3 text-center">
                            <i class="fab fa-php fa-2x text-primary mb-2"></i>
                            <h6 class="mb-0">PHP Version</h6>
                            <p class="text-muted mb-0">{{ phpversion() }}</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3 text-center">
                            <i class="fas fa-database fa-2x text-primary mb-2"></i>
                            <h6 class="mb-0">Database</h6>
                            <p class="text-muted mb-0">{{ config('database.default') }}</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3 text-center">
                            <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                            <h6 class="mb-0">Server Time</h6>
                            <p class="text-muted mb-0">{{ now()->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            <strong>System Timezone:</strong> {{ config('app.timezone') }}
                            <span class="float-end">
                                <i class="fas fa-globe"></i> 
                                {{ date_default_timezone_get() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Admins List -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-user-tie"></i> Recent Administrators</h5>
            </div>
            <div class="card-body p-0">
                @php
                    $recentAdmins = \App\Models\User::whereIn('role', ['admin', 'master_admin'])
                        ->withCount('createdQuizzes')
                        ->orderByDesc('created_at')
                        ->take(5)
                        ->get();
                @endphp
                
                @if($recentAdmins->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Quizzes Created</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAdmins as $admin)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2" style="width: 32px; height: 32px;">
                                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                                </div>
                                                <strong>{{ $admin->name }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $admin->email }}</td>
                                        <td>
                                            @if($admin->role == 'master_admin')
                                                <span class="badge bg-danger">Master Admin</span>
                                            @else
                                                <span class="badge bg-info">Admin</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-primary">{{ $admin->created_quizzes_count }}</span></td>
                                        <td>{{ $admin->created_at->format('M d, Y') }}</td>
                                        <td>
                                            @if($admin->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('master-admin.admins.show', $admin) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('master-admin.admins.edit', $admin) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No administrators found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // See More / Show Less functionality for activities
        const $seeMoreBtn = $('#seeMoreActivitiesBtn');
        const $showLessBtn = $('#showLessActivitiesBtn');
        const $moreActivities = $('#moreActivities');
        
        if ($seeMoreBtn.length) {
            $seeMoreBtn.on('click', function() {
                $moreActivities.slideDown(300, function() {
                    $seeMoreBtn.hide();
                    $showLessBtn.show();
                });
            });
        }
        
        if ($showLessBtn.length) {
            $showLessBtn.on('click', function() {
                $moreActivities.slideUp(300, function() {
                    $showLessBtn.hide();
                    $seeMoreBtn.show();
                    // Scroll back to the activity section
                    $('html, body').animate({
                        scrollTop: $('#activityList').offset().top - 100
                    }, 300);
                });
            });
        }
        
        // Auto-refresh activities every 30 seconds (optional)
        let autoRefresh = setInterval(function() {
            $.ajax({
                url: '{{ route("master-admin.activities.latest") }}',
                method: 'GET',
                success: function(response) {
                    if (response.html) {
                        // Update the activities list with new data
                        $('#initialActivities').html(response.html);
                        // Update count
                        $('#totalActivityCount').text(response.total);
                    }
                }
            });
        }, 30000);
        
        // Clear interval on page unload
        $(window).on('beforeunload', function() {
            clearInterval(autoRefresh);
        });
    });
</script>
@endpush
@endsection