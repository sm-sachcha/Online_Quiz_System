@extends('layouts.admin')

@section('title', 'Admin Details - ' . $admin->name)

@section('content')
<div class="row">
    <!-- Admin Information Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-tie"></i> Admin Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                     style="width: 120px; height: 120px; font-size: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                </div>
                
                <h4>{{ $admin->name }}</h4>
                <p class="text-muted">
                    <i class="fas fa-envelope"></i> {{ $admin->email }}
                </p>
                
                <div class="mb-3">
                    @if($admin->role == 'master_admin')
                        <span class="badge bg-danger">
                            <i class="fas fa-crown"></i> Master Admin
                        </span>
                    @else
                        <span class="badge bg-info">
                            <i class="fas fa-user-shield"></i> Admin
                        </span>
                    @endif
                    
                    @if($admin->is_active)
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                    @else
                        <span class="badge bg-danger ms-2">
                            <i class="fas fa-ban"></i> Inactive
                        </span>
                    @endif
                </div>
                
                <hr>
                
                <div class="text-start">
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-phone"></i> Phone:</td>
                            <td><strong>{{ $admin->profile->phone ?? 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-calendar-alt"></i> Joined:</td>
                            <td><strong>{{ $admin->created_at->format('M d, Y') }}</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clock"></i> Last Login:</td>
                            <td>
                                @if($admin->activities()->where('action', 'login')->latest()->first())
                                    <strong>{{ $admin->activities()->where('action', 'login')->latest()->first()->created_at->diffForHumans() }}</strong>
                                @else
                                    <strong>Never</strong>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('master-admin.admins.edit', $admin) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Admin
                    </a>
                    <a href="{{ route('master-admin.admins.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics and Activity -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3>{{ $stats['quizzes_created'] }}</h3>
                                <p class="mb-0">Quizzes Created</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3>{{ $stats['categories_created'] }}</h3>
                                <p class="mb-0">Categories Created</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3>{{ $stats['questions_created'] }}</h3>
                                <p class="mb-0">Questions Created</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Created Quizzes -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Recently Created Quizzes</h5>
            </div>
            <div class="card-body p-0">
                @if($createdQuizzes->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($createdQuizzes as $quiz)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $quiz->title }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-tag"></i> {{ $quiz->category->name }} |
                                            <i class="fas fa-question"></i> {{ $quiz->questions_count }} questions
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $quiz->is_published ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $quiz->is_published ? 'Published' : 'Draft' }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $quiz->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No quizzes created yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activities</h5>
            </div>
            <div class="card-body p-0">
                @if($recentActivities->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentActivities as $activity)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        @if($activity->action == 'login')
                                            <i class="fas fa-sign-in-alt text-success"></i>
                                        @elseif($activity->action == 'logout')
                                            <i class="fas fa-sign-out-alt text-secondary"></i>
                                        @else
                                            <i class="fas fa-edit text-info"></i>
                                        @endif
                                        <strong>{{ ucfirst($activity->action) }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> {{ $activity->ip_address }}
                                        </small>
                                    </div>
                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                </div>
                                @if($activity->details)
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            {{ json_encode($activity->details) }}
                                        </small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent activities.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection