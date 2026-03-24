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
                <form action="{{ route('master-admin.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="app_name" class="form-label">Application Name</label>
                        <input type="text" class="form-control @error('app_name') is-invalid @enderror" 
                               id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
                        @error('app_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="app_url" class="form-label">Application URL</label>
                        <input type="url" class="form-control @error('app_url') is-invalid @enderror" 
                               id="app_url" name="app_url" value="{{ old('app_url', $settings['app_url']) }}" required>
                        @error('app_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone" required>
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ $settings['timezone'] == $tz ? 'selected' : '' }}>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> Changing these settings will update your .env file. The application will reload automatically.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection