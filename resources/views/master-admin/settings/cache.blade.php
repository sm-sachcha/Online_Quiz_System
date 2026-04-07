@extends('layouts.admin')

@section('title', 'Cache Management')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-database"></i> Cache Management</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Warning:</strong> Clearing cache will reset temporary data. Your application data is safe.
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-code fa-3x text-primary mb-3"></i>
                                <h5>Clear Configuration Cache</h5>
                                <p class="text-muted">Clears the cached configuration files</p>
                                <form action="{{ route('master-admin.settings.cache.clear') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="config">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-trash"></i> Clear Config Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-route fa-3x text-success mb-3"></i>
                                <h5>Clear Route Cache</h5>
                                <p class="text-muted">Clears the cached routes</p>
                                <form action="{{ route('master-admin.settings.cache.clear') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="route">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-trash"></i> Clear Route Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-eye fa-3x text-info mb-3"></i>
                                <h5>Clear View Cache</h5>
                                <p class="text-muted">Clears the cached views</p>
                                <form action="{{ route('master-admin.settings.cache.clear') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="view">
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-trash"></i> Clear View Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-bolt fa-3x text-warning mb-3"></i>
                                <h5>Clear Application Cache</h5>
                                <p class="text-muted">Clears the application cache</p>
                                <form action="{{ route('master-admin.settings.cache.clear') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="cache">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-trash"></i> Clear App Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-eraser fa-3x mb-3"></i>
                                <h5>Clear All Cache</h5>
                                <p class="text-white">Clear all cached data including config, routes, views, and application cache</p>
                                <form action="{{ route('master-admin.settings.cache.clear') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="all">
                                    <button type="submit" class="btn btn-light text-danger" 
                                            onclick="return confirm('Are you sure you want to clear ALL cache?')">
                                        <i class="fas fa-trash-alt"></i> Clear All Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection