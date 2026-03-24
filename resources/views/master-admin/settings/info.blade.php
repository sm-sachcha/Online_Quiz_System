@extends('layouts.admin')

@section('title', 'System Information')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 250px;">Laravel Version</th>
                        <td><strong>{{ $info['laravel_version'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>PHP Version</th>
                        <td><strong>{{ $info['php_version'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>Server Software</th>
                        <td><strong>{{ $info['server_software'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>Server OS</th>
                        <td><strong>{{ $info['server_os'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>Database Connection</th>
                        <td><strong>{{ $info['database_connection'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>Database Name</th>
                        <td><strong>{{ $info['database_name'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>Memory Limit</th>
                        <td><strong>{{ $info['memory_limit'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>Max Execution Time</th>
                        <td><strong>{{ $info['max_execution_time'] }} seconds</strong></td>
                    </tr>
                </table>

                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle"></i> System is running optimally.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection