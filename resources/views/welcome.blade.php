@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="row">
    <div class="col-md-12 text-center py-5">
        <h1 class="display-3 mb-4">Welcome to {{ config('app.name') }}</h1>
        <p class="lead mb-4"></p>
        
        <div class="row mt-5">
            <div class="col-md-15 mb-15">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Take Quizzes</h5>
                        <p class="card-text">Test your knowledge, compete with others.</p>
                    </div>
                </div>
            </div>
        </div>
        @guest
        @else
            <!-- <div class="mt-4">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            </div> -->
        @endguest
    </div>
</div>
@endsection