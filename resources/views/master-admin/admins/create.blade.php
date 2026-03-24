@extends('layouts.admin')

@section('title', 'Create Administrator')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New Administrator</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle"></i> 
                    <strong>You are creating a new administrator.</strong> They will be able to access the admin panel after login.
                </div>
                
                <form action="{{ route('master-admin.admins.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required autofocus>
                        </div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required>
                            </div>
                            <small class="text-muted">Minimum 8 characters</small>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>General Admin</option>
                            <option value="master_admin" {{ old('role') == 'master_admin' ? 'selected' : '' }}>Master Admin</option>
                        </select>
                        <div class="mt-2">
                            <small class="text-muted d-block">
                                <i class="fas fa-info-circle text-info"></i> 
                                <strong>General Admin:</strong> Can manage quizzes, categories, questions, and view reports.
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-info-circle text-danger"></i> 
                                <strong>Master Admin:</strong> Has all admin privileges plus can manage other admins and system settings.
                            </small>
                        </div>
                        @error('role')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}">
                        </div>
                        @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">
                            <i class="fas fa-check-circle text-success"></i> Activate Account
                        </label>
                        <small class="text-muted d-block">Inactive admins cannot log in to the system</small>
                    </div>

                    <div class="alert alert-warning" id="masterAdminWarning" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Warning:</strong> Master Admins have full system access including the ability to delete other admins and modify system settings. Please assign this role carefully.
                    </div>

                    <div class="alert alert-info" id="adminInfo" style="display: none;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>General Admin:</strong> This role has access to manage quizzes, categories, questions, and view reports. They cannot manage other administrators.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Create Administrator
                        </button>
                        <a href="{{ route('master-admin.admins.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const roleSelect = document.getElementById('role');
    const masterAdminWarning = document.getElementById('masterAdminWarning');
    const adminInfo = document.getElementById('adminInfo');
    
    roleSelect.addEventListener('change', function() {
        if (this.value === 'master_admin') {
            masterAdminWarning.style.display = 'block';
            adminInfo.style.display = 'none';
        } else if (this.value === 'admin') {
            masterAdminWarning.style.display = 'none';
            adminInfo.style.display = 'block';
        } else {
            masterAdminWarning.style.display = 'none';
            adminInfo.style.display = 'none';
        }
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = getPasswordStrength(this.value);
            let strengthText = document.getElementById('password-strength');
            if (!strengthText) {
                strengthText = document.createElement('small');
                strengthText.id = 'password-strength';
                this.parentNode.parentNode.appendChild(strengthText);
            }
            strengthText.textContent = 'Strength: ' + strength;
            strengthText.className = 'text-' + (strength === 'Strong' ? 'success' : strength === 'Medium' ? 'warning' : 'danger');
        });
    }
    
    function getPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;
        
        if (strength >= 4) return 'Strong';
        if (strength >= 2) return 'Medium';
        return 'Weak';
    }
</script>
@endpush
@endsection