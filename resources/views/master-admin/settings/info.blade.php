@extends('layouts.admin')

@section('title', 'System Information')

@section('content')
<style>
    .info-card {
        transition: all 0.3s ease;
    }
    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .timezone-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .live-time-display {
        font-family: monospace;
        font-size: 24px;
        font-weight: bold;
        background: rgba(255,255,255,0.2);
        padding: 10px 20px;
        border-radius: 8px;
        display: inline-block;
    }
    .utc-offset {
        background: rgba(255,255,255,0.15);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 14px;
        display: inline-block;
        margin-top: 8px;
    }
    .timezone-comparison {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
    }
    .timezone-comparison .row {
        border-bottom: 1px solid #e9ecef;
        padding: 10px 0;
    }
    .timezone-comparison .row:last-child {
        border-bottom: none;
    }
    .offset-badge {
        font-family: monospace;
        font-size: 14px;
        font-weight: bold;
    }
    .positive-offset {
        color: #28a745;
    }
    .negative-offset {
        color: #dc3545;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="timezone-info mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="fas fa-clock"></i> Timezone Configuration</h4>
                    <p class="mb-0">System Timezone: <strong>{{ $info['timezone'] }}</strong></p>
                    <p class="mb-0">PHP Timezone: <strong>{{ $info['php_timezone'] }}</strong></p>
                    @php
                        $currentTimezone = new DateTimeZone($info['timezone']);
                        $utcTimezone = new DateTimeZone('UTC');
                        $now = new DateTime('now', $utcTimezone);
                        $offset = $currentTimezone->getOffset($now);
                        $offsetHours = $offset / 3600;
                        $offsetSign = $offsetHours >= 0 ? '+' : '';
                        $utcOffset = "UTC {$offsetSign}{$offsetHours}";
                    @endphp
                    <div class="utc-offset mt-2">
                        <i class="fas fa-globe"></i> UTC Offset: <strong>{{ $utcOffset }}</strong>
                        @if($offsetHours != 0)
                            <span class="ms-2">
                                @if($offsetHours > 0)
                                    <span class="text-warning">({{ $offsetHours }} hours ahead of UTC)</span>
                                @else
                                    <span class="text-info">({{ abs($offsetHours) }} hours behind UTC)</span>
                                @endif
                            </span>
                        @else
                            <span class="text-success">(Same as UTC)</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="live-time-display" id="liveTimeDisplay">
                        {{ $info['current_time'] }}
                    </div>
                    <br>
                    <small>Local Time ({{ $info['timezone'] }})</small>
                    <div class="mt-2">
                        <small>UTC Time: <strong id="utcTime">{{ $info['current_time_utc'] }}</strong> UTC</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fab fa-laravel fa-3x text-danger mb-3"></i>
                                <h6 class="mb-0">Laravel Version</h6>
                                <p class="text-muted mb-0">{{ $info['laravel_version'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fab fa-php fa-3x text-primary mb-3"></i>
                                <h6 class="mb-0">PHP Version</h6>
                                <p class="text-muted mb-0">{{ $info['php_version'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-server fa-3x text-success mb-3"></i>
                                <h6 class="mb-0">Server Software</h6>
                                <p class="text-muted mb-0">{{ $info['server_software'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x text-info mb-3"></i>
                                <h6 class="mb-0">Database</h6>
                                <p class="text-muted mb-0">{{ ucfirst($info['database_connection']) }}</p>
                                <small class="text-muted">{{ $info['database_name'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fab fa-windows fa-3x text-secondary mb-3"></i>
                                <h6 class="mb-0">Operating System</h6>
                                <p class="text-muted mb-0">{{ $info['server_os'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-memory fa-3x text-warning mb-3"></i>
                                <h6 class="mb-0">Memory Limit</h6>
                                <p class="text-muted mb-0">{{ $info['memory_limit'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-hourglass-half fa-3x text-danger mb-3"></i>
                                <h6 class="mb-0">Max Execution Time</h6>
                                <p class="text-muted mb-0">{{ $info['max_execution_time'] }} seconds</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-upload fa-3x text-success mb-3"></i>
                                <h6 class="mb-0">Upload Max File Size</h6>
                                <p class="text-muted mb-0">{{ $info['upload_max_filesize'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timezone Comparison Table -->
                <div class="timezone-comparison mt-4">
                    <h6><i class="fas fa-globe"></i> Timezone Comparison</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Your Local Time ({{ $info['timezone'] }})</strong>
                            <div id="localTimeDisplay" class="mt-2">
                                <span class="offset-badge positive-offset">{{ $info['current_time'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>UTC Time</strong>
                            <div id="utcTimeDisplay" class="mt-2">
                                <span class="offset-badge">{{ $info['current_time_utc'] }} UTC</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <strong>Timezone Offset:</strong>
                            <span class="offset-badge {{ $offsetHours >= 0 ? 'positive-offset' : 'negative-offset' }}">
                                {{ $utcOffset }}
                            </span>
                            <span class="ms-2 text-muted">
                                ({{ $offsetHours >= 0 ? '+' : '-' }}{{ abs($offsetHours) }} hours from UTC)
                            </span>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> UTC (Coordinated Universal Time) is the primary time standard by which the world regulates clocks and time. 
                                Your selected timezone is <strong>{{ $info['timezone'] }}</strong>, which is 
                                <strong>{{ $offsetHours >= 0 ? $offsetHours . ' hours ahead' : abs($offsetHours) . ' hours behind' }}</strong> of UTC.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>To change the system timezone:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Go to <a href="{{ route('master-admin.settings.index') }}" class="alert-link">System Settings</a></li>
                        <li>Select your preferred timezone from the dropdown</li>
                        <li>Click "Save Settings & Clear Cache"</li>
                        <li>The system will automatically apply the new timezone</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const systemTimezone = '{{ $info["timezone"] }}';
    const utcOffset = {{ $offsetHours }};
    
    function updateLiveTimes() {
        const now = new Date();
        
        // Update local time display
        try {
            const localOptions = {
                timeZone: systemTimezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            const localFormatter = new Intl.DateTimeFormat('en-US', localOptions);
            const localFormatted = localFormatter.format(now);
            const localParts = localFormatted.split(/[/, :]+/);
            
            if (localParts.length >= 6) {
                const localTimeString = `${localParts[2]}-${localParts[0]}-${localParts[1]} ${localParts[3]}:${localParts[4]}:${localParts[5]}`;
                document.getElementById('liveTimeDisplay').textContent = localTimeString;
                document.getElementById('localTimeDisplay').innerHTML = `<span class="offset-badge positive-offset">${localTimeString}</span>`;
            }
        } catch (e) {
            console.error('Local time error:', e);
        }
        
        // Update UTC time display
        const utcYear = now.getUTCFullYear();
        const utcMonth = String(now.getUTCMonth() + 1).padStart(2, '0');
        const utcDay = String(now.getUTCDate()).padStart(2, '0');
        const utcHours = String(now.getUTCHours()).padStart(2, '0');
        const utcMinutes = String(now.getUTCMinutes()).padStart(2, '0');
        const utcSeconds = String(now.getUTCSeconds()).padStart(2, '0');
        const utcTimeString = `${utcYear}-${utcMonth}-${utcDay} ${utcHours}:${utcMinutes}:${utcSeconds}`;
        
        document.getElementById('utcTime').textContent = utcTimeString;
        document.getElementById('utcTimeDisplay').innerHTML = `<span class="offset-badge">${utcTimeString} UTC</span>`;
    }
    
    // Update every second
    setInterval(updateLiveTimes, 1000);
    updateLiveTimes();
    
    // Display offset info
    console.log('Timezone offset:', utcOffset);
</script>
@endpush
@endsection