@extends('layouts.admin')

@section('title', 'Reports Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4"><i class="fas fa-chart-bar"></i> Reports Dashboard</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-4x text-primary mb-3"></i>
                <h5 class="card-title">Quiz Performance Report</h5>
                <p class="card-text">View detailed quiz performance reports including scores, completion rates, and participant statistics.</p>
                <a href="{{ route('admin.reports.quiz-performance') }}" class="btn btn-primary">
                    View Report <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow">
            <div class="card-body text-center">
                <i class="fas fa-users fa-4x text-success mb-3"></i>
                <h5 class="card-title">User Activity Report</h5>
                <p class="card-text">Monitor user activity, login patterns, and engagement metrics across the platform.</p>
                <a href="{{ route('admin.reports.user-activity') }}" class="btn btn-success">
                    View Report <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow">
            <div class="card-body text-center">
                <i class="fas fa-chart-pie fa-4x text-info mb-3"></i>
                <h5 class="card-title">System Overview</h5>
                <p class="card-text">Get a comprehensive overview of system health, user growth, and quiz statistics.</p>
                <a href="{{ route('admin.reports.system-overview') }}" class="btn btn-info">
                    View Report <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-download"></i> Quick Export Options</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reports.export') }}" method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label for="quiz_id" class="form-label">Select Quiz</label>
                        <select name="quiz_id" id="quiz_id" class="form-select" required>
                            <option value="">Choose a quiz...</option>
                            @foreach(\App\Models\Quiz::where('is_published', true)->get() as $quiz)
                                <option value="{{ $quiz->id }}">{{ $quiz->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="format" class="form-label">Export Format</label>
                        <select name="format" id="format" class="form-select" required>
                            <option value="csv">CSV (Excel Compatible)</option>
                            <option value="pdf" disabled>PDF (Coming Soon)</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('admin.reports.user-activity') }}?export=csv" class="btn btn-success w-100">
                            <i class="fas fa-file-csv"></i> Export Activity
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection