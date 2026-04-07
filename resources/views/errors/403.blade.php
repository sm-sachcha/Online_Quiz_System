@extends('layouts.app')

@section('title', '403 - Unauthorized')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 text-center py-5">
        <div class="error-page">
            <h1 class="display-1 text-warning">403</h1>
            <h2 class="mb-4">Access Denied</h2>
            <p class="lead mb-4">You do not have permission to access this page.</p>
            <a href="{{ url()->previous() }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            <a href="{{ url('/') }}" class="btn btn-secondary">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
    </div>
</div>
@endsection