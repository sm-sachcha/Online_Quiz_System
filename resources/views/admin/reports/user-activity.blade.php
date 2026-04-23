@extends('layouts.admin')

@section('title', 'User Activity Report')

@section('content')
<style>
    .activity-report-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
    }
    .activity-report-header {
        background: linear-gradient(135deg, #0f766e 0%, #0891b2 100%);
        border: none;
        padding: 1rem 1.25rem;
    }
    .activity-table-card {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }
    .activity-table-card .card-header {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.25rem;
    }
    #activitiesTable thead th {
        background: #f8fafc;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom: 1px solid #e2e8f0;
    }
    #activitiesTable tbody td {
        vertical-align: middle;
        border-color: #eef2f7;
    }
    #activitiesTable tbody tr {
        transition: background-color 0.18s ease, transform 0.18s ease;
    }
    #activitiesTable tbody tr:hover {
        background: #f8fafc;
    }
    .activity-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #334155 0%, #0f172a 100%);
        color: #fff;
        font-weight: 700;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
    }
    .activity-user-agent {
        display: inline-block;
        max-width: 280px;
        color: #64748b;
    }
    .activity-pagination-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding-top: 1rem;
    }
    @media (max-width: 768px) {
        .activity-user-agent {
            max-width: 160px;
        }
        .activity-pagination-wrap {
            justify-content: center;
        }
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="card activity-report-card mb-4">
            <div class="card-header activity-report-header text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> User Activity Report</h5>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="user_id" class="form-label">User</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                        <a href="{{ route('admin.reports.user-activity') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title">Total Activities</h6>
                                <h3>{{ $summary['total_activities'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">Unique Users</h6>
                                <h3>{{ $summary['unique_users'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title">Most Common Action</h6>
                                <h5>{{ $summary['most_common_action']->action ?? 'N/A' }}</h5>
                                <small>{{ $summary['most_common_action']->count ?? 0 }} times</small>
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- Activity Chart -->
                @if($activityByDay->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Activity Timeline</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="activityChart" height="300"></canvas>
                        </div>
                    </div>
                @endif

                <!-- Activities Table -->
                <!-- <div class="card activity-table-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list"></i> Recent Activities</h6>
                    </div>
                    <div class="card-body">
                        @if($activities->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover" id="activitiesTable">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>IP Address</th>
                                            <th>User Agent</th>
                                            <th>Details</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activities as $activity)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="activity-user-avatar me-2">
                                                            {{ strtoupper(substr($activity->user->name ?? 'U', 0, 1)) }}
                                                        </div>
                                                        <strong>{{ $activity->user->name ?? 'Deleted User' }}</strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($activity->action == 'login')
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-sign-in-alt"></i> Login
                                                        </span>
                                                    @elseif($activity->action == 'logout')
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-sign-out-alt"></i> Logout
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info">{{ $activity->action }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <code>{{ $activity->ip_address }}</code>
                                                </td>
                                                <td>
                                                    <small class="activity-user-agent" title="{{ $activity->user_agent }}">
                                                        {{ Str::limit($activity->user_agent, 40) }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @if($activity->details)
                                                        <button class="btn btn-sm btn-outline-info" onclick="showDetails({{ json_encode($activity->details) }})">
                                                            <i class="fas fa-info-circle"></i> View
                                                        </button>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small>{{ $activity->created_at->format('M d, Y H:i:s') }}</small>
                                                    <br>
                                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="activity-pagination-wrap">
                                {{ $activities->withQueryString()->links('admin.button.next-previous') }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                                <h5>No Activities Found</h5>
                                <p class="text-muted">No user activities match your search criteria.</p>
                                <a href="{{ route('admin.reports.user-activity') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Clear Filters
                                </a>
                            </div>
                        @endif
                    </div>
                </div> -->

                <!-- Export Button -->
                <!-- <div class="mt-3 text-end">
                    <a href="{{ route('admin.reports.user-activity', array_merge(request()->all(), ['export' => 'csv'])) }}" 
                       class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </a>
                </div> -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if($activityByDay->count() > 0)
    const ctx = document.getElementById('activityChart').getContext('2d');
    const activityData = @json($activityByDay);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: activityData.map(item => item.date),
            datasets: [{
                label: 'Number of Activities',
                data: activityData.map(item => item.count),
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Activities: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Number of Activities'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
    @endif
    
    function showDetails(details) {
        let detailsText = '';
        if (typeof details === 'object') {
            detailsText = JSON.stringify(details, null, 2);
        } else {
            detailsText = details;
        }
        
        // Create modal for details
        const modalHtml = `
            <div class="modal fade" id="detailsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title"><i class="fas fa-info-circle"></i> Activity Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow: auto;">${detailsText}</pre>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        if ($('#detailsModal').length) {
            $('#detailsModal').remove();
        }
        
        // Add modal to body
        $('body').append(modalHtml);
        
        // Show modal
        $('#detailsModal').modal('show');
        
        // Remove modal when hidden
        $('#detailsModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }
</script>
@endpush
@endsection
