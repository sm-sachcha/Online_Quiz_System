@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cog"></i> System Settings</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {!! session('success') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('master-admin.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="app_name" class="form-label">Application Name</label>
                            <input type="text" class="form-control @error('app_name') is-invalid @enderror" 
                                   id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
                            @error('app_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="app_url" class="form-label">Application URL</label>
                            <input type="url" class="form-control @error('app_url') is-invalid @enderror" 
                                   id="app_url" name="app_url" value="{{ old('app_url', $settings['app_url']) }}" required>
                            @error('app_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="timezone" class="form-label">System Timezone</label>
                        <select class="form-select @error('timezone') is-invalid @enderror" 
                                id="timezone" name="timezone" required>
                            <option value="">Select Timezone</option>
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" {{ $settings['timezone'] == $tz ? 'selected' : '' }}>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        <!-- Live Time Preview -->
                        <div class="mt-3 p-3 bg-light rounded" id="timezonePreview">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-clock"></i> Current Time Preview:</strong>
                                    <div class="mt-2">
                                        <span class="badge bg-dark p-2" id="previewTime" style="font-size: 16px;">--:--:--</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-calendar"></i> Current Date:</strong>
                                    <div class="mt-2">
                                        <span class="badge bg-secondary p-2" id="previewDate">--</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted" id="previewOffset">Select a timezone to see preview</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Changing the timezone will affect all date/time displays throughout the system, including quiz schedules and user activity timestamps.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const timezoneSelect = $('#timezone');
        const previewTime = $('#previewTime');
        const previewDate = $('#previewDate');
        const previewOffset = $('#previewOffset');
        
        let updateInterval;
        
        function getUTCOffset(timezone) {
            if (!timezone) return '';
            try {
                const now = new Date();
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: timezone,
                    timeZoneName: 'shortOffset'
                });
                const parts = formatter.formatToParts(now);
                const offsetPart = parts.find(part => part.type === 'timeZoneName');
                return offsetPart ? offsetPart.value : '';
            } catch(e) {
                return '';
            }
        }
        
        function updatePreview() {
            const selectedTimezone = timezoneSelect.val();
            if (!selectedTimezone) {
                previewTime.text('--:--:--');
                previewDate.text('--');
                previewOffset.text('Select a timezone to see preview');
                return;
            }
            
            try {
                const now = new Date();
                
                // Format time
                const timeOptions = {
                    timeZone: selectedTimezone,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                };
                const formattedTime = new Intl.DateTimeFormat('en-US', timeOptions).format(now);
                previewTime.text(formattedTime);
                
                // Format date
                const dateOptions = {
                    timeZone: selectedTimezone,
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    weekday: 'long'
                };
                const formattedDate = new Intl.DateTimeFormat('en-US', dateOptions).format(now);
                previewDate.text(formattedDate);
                
                // Get offset
                const offset = getUTCOffset(selectedTimezone);
                previewOffset.html(`<i class="fas fa-globe"></i> UTC Offset: <strong>${offset}</strong>`);
                
            } catch(e) {
                previewTime.text('Error');
                previewDate.text('Error');
                previewOffset.text('Error loading timezone');
            }
        }
        
        // Update preview when timezone changes
        timezoneSelect.on('change', function() {
            updatePreview();
            $('#timezonePreview').css('border-left', '3px solid #ffc107');
            setTimeout(() => {
                $('#timezonePreview').css('border-left', '');
            }, 1000);
        });
        
        // Start live updates
        updatePreview();
        updateInterval = setInterval(updatePreview, 1000);
        
        // Clean up on page unload
        $(window).on('beforeunload', function() {
            if (updateInterval) clearInterval(updateInterval);
        });
    });
</script>
@endpush
@endsection