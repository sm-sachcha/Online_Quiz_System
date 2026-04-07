@extends('layouts.app')

@section('title', '404 - Page Not Found')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 text-center py-5">
        <div class="error-page">
            <h1 class="display-1 text-danger">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            <p class="lead mb-4">The page you are looking for does not exist.</p>
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