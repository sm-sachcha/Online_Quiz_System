@extends('layouts.admin')

@section('title', 'Master Admin Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-crown"></i> Welcome, <strong>{{ Auth::user()->name }}</strong>! You have full system access as Master Admin.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Admins</h6>
                        <h2 class="mb-0">{{ \App\Models\User::whereIn('role', ['admin', 'master_admin'])->count() }}</h2>
                    </div>
                    <i class="fas fa-user-tie fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">General Admins</h6>
                        <h2 class="mb-0">{{ \App\Models\User::where('role', 'admin')->count() }}</h2>
                    </div>
                    <i class="fas fa-user-shield fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Master Admins</h6>
                        <h2 class="mb-0">{{ \App\Models\User::where('role', 'master_admin')->count() }}</h2>
                    </div>
                    <i class="fas fa-crown fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Regular Users</h6>
                        <h2 class="mb-0">{{ \App\Models\User::where('role', 'user')->count() }}</h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="{{ route('master-admin.admins.create') }}" class="list-group-item list-group-item-action bg-success text-white">
                        <i class="fas fa-user-plus"></i> Add New Administrator
                        <span class="badge bg-light text-dark float-end">Create Admin</span>
                    </a>
                    <a href="{{ route('master-admin.admins.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-tie"></i> Manage All Administrators
                        <span class="badge bg-primary float-end">View All</span>
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line"></i> Admin Dashboard
                        <span class="badge bg-info float-end">View</span>
                    </a>
                    <a href="{{ route('master-admin.settings.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog"></i> System Settings
                        <span class="badge bg-info float-end">Configure</span>
                    </a>
                    <a href="{{ route('master-admin.settings.maintenance') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-tools"></i> Maintenance Mode
                        <span class="badge bg-warning float-end">Toggle</span>
                    </a>
                    <a href="{{ route('master-admin.settings.cache') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-database"></i> Cache Management
                        <span class="badge bg-secondary float-end">Clear Cache</span>
                    </a>
                    <a href="{{ route('master-admin.settings.logs') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt"></i> System Logs
                        <span class="badge bg-danger float-end">View</span>
                    </a>
                    <a href="{{ route('master-admin.settings.info') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-info-circle"></i> System Information
                        <span class="badge bg-success float-end">Details</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Recent Admin Activity</h5>
            </div>
            <div class="card-body p-0">
                @php
                    $recentActivities = \App\Models\UserActivity::with('user')
                        ->whereHas('user', function($q) {
                            $q->whereIn('role', ['admin', 'master_admin']);
                        })
                        ->latest()
                        ->take(10)
                        ->get();
                @endphp
                
                @if($recentActivities->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentActivities as $activity)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        @if($activity->user->role == 'master_admin')
                                            <span class="badge bg-danger me-1">Master</span>
                                        @else
                                            <span class="badge bg-info me-1">Admin</span>
                                        @endif
                                        <strong>{{ $activity->user->name }}</strong>
                                        <span class="text-muted">{{ $activity->action }}</span>
                                    </div>
                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> {{ $activity->ip_address }}
                                </small>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent activities.</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Administrator List</h5>
            </div>
            <div class="card-body p-0">
                @php
                    $allAdmins = \App\Models\User::whereIn('role', ['admin', 'master_admin'])
                        ->orderBy('role', 'desc')
                        ->orderBy('name')
                        ->take(5)
                        ->get();
                @endphp
                
                @if($allAdmins->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($allAdmins as $admin)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @if($admin->role == 'master_admin')
                                            <span class="badge bg-danger">Master Admin</span>
                                        @else
                                            <span class="badge bg-info">General Admin</span>
                                        @endif
                                        <strong>{{ $admin->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $admin->email }}</small>
                                    </div>
                                    <div class="text-end">
                                        @if($admin->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center p-3">
                        <a href="{{ route('master-admin.admins.index') }}" class="btn btn-sm btn-outline-primary">
                            View All Administrators <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted">No administrators found.</p>
                        <a href="{{ route('master-admin.admins.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add First Administrator
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection