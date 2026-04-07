@extends('layouts.admin')

@section('title', 'Maintenance Mode')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-tools"></i> Maintenance Mode</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    When maintenance mode is enabled, only administrators can access the site. Users will see a maintenance page.
                </div>

                @if(app()->isDownForMaintenance())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Maintenance Mode is ACTIVE</strong>
                        <p class="mb-0 mt-2">The site is currently in maintenance mode. Users cannot access the site.</p>
                    </div>
                    
                    <form action="{{ route('master-admin.settings.maintenance.toggle') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-play"></i> Bring Site Online
                        </button>
                    </form>
                @else
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <strong>Site is LIVE</strong>
                        <p class="mb-0 mt-2">The site is currently accessible to all users.</p>
                    </div>
                    
                    <form action="{{ route('master-admin.settings.maintenance.toggle') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-lg w-100" onclick="return confirm('Are you sure you want to put the site in maintenance mode? Users will not be able to access the site.')">
                            <i class="fas fa-pause"></i> Enable Maintenance Mode
                        </button>
                    </form>
                @endif

                <hr class="my-4">

                <h6><i class="fas fa-info-circle"></i> What happens in maintenance mode?</h6>
                <ul class="text-muted">
                    <li>Regular users see a maintenance page</li>
                    <li>Administrators can still log in and access the admin panel</li>
                    <li>API requests return a 503 Service Unavailable response</li>
                    <li>Scheduled tasks continue to run in the background</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection