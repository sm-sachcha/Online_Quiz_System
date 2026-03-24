@extends('layouts.admin')

@section('title', 'System Logs')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-alt"></i> System Logs</h5>
                <form action="{{ route('master-admin.settings.logs.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to clear all logs?')">
                        <i class="fas fa-trash"></i> Clear Logs
                    </button>
                </form>
            </div>
            <div class="card-body">
                @if(count($logs) > 0)
                    <div class="table-responsive">
                        <pre class="bg-dark text-light p-3" style="max-height: 600px; overflow-y: auto;">
                            <code>@foreach($logs as $log){{ $log }}@endforeach</code>
                        </pre>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                        <h5>No Logs Found</h5>
                        <p class="text-muted">The log file is empty or doesn't exist.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection