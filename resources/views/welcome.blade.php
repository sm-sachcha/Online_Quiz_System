@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="row">
    <div class="col-md-12 text-center py-5">
        <h1 class="display-3 mb-4">Welcome to {{ config('app.name') }}</h1>
        <p class="lead mb-4">Test your knowledge, compete with others, and earn rewards!</p>
        
        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Take Quizzes</h5>
                        <p class="card-text">Choose from a variety of quizzes and test your knowledge</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Compete & Win</h5>
                        <p class="card-text">Compete with others on live leaderboards</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Track Progress</h5>
                        <p class="card-text">Monitor your performance and earn certificates</p>
                    </div>
                </div>
            </div>
        </div>
        
        @guest
            <div class="mt-4">
                <a href="{{ route('register') }}" class="btn btn-success btn-lg me-2">
                    <i class="fas fa-user-plus"></i> Get Started
                </a>
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        @else
            <div class="mt-4">
                <a href="{{ route('user.dashboard') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            </div>
        @endguest
    </div>
</div>
@endsection