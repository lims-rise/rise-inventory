@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="rp-head">
    <div class="rp-head-title">
        <span class="rp-head-icon"><i class="bi bi-person-circle"></i></span>
        <div>
            <ol class="rp-crumb"><li><a href="{{ url('welcome') }}"><i class="bi bi-house-door"></i></a></li><li>Profile</li></ol>
            <h1>My Profile</h1>
            <p>{{ $user->email }} · {{ $user->level?->nama_level }}</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <div class="row">
        <div class="col-lg-4">
            <div class="rp-card sf-panel">
                <label class="sf-avatar" for="f-images" title="Change photo">
                    <img src="{{ $user->avatarUrl() }}" alt="" id="avatar-img">
                    <span class="sf-avatar-cam"><i class="bi bi-camera"></i></span>
                </label>
                <input type="file" id="f-images" name="images" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                <h3 class="sf-preview-name" id="preview-name">{{ $user->full_name }}</h3>
                <p class="sf-preview-dept">{{ $user->level?->nama_level }}</p>
                @error('images')<p class="text-danger">{{ $message }}</p>@enderror
                <ul class="cf-facts">
                    <li><span>Default location</span><b>{{ $user->location?->name ?: '—' }}</b></li>
                    <li><span>Linked staff</span><b>@if ($user->staff)<a href="{{ url('staff/read?id='.$user->staff->id) }}">{{ $user->staff->name }}</a>@else — @endif</b></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-8">
            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-person"></i> Details</h3>
                <div class="form-group {{ $errors->has('full_name') ? 'has-error' : '' }}">
                    <label for="f-full_name">Full Name <span class="rp-req">*</span></label>
                    <input type="text" id="f-full_name" name="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}" maxlength="50">
                    @error('full_name')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" class="form-control" value="{{ $user->email }}" readonly>
                    <p class="rp-help"><i class="bi bi-lock"></i> Ask an administrator to change your login email.</p>
                </div>
            </section>
            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-key"></i> Change password</h3>
                <div class="form-group {{ $errors->has('current_password') ? 'has-error' : '' }}">
                    <label for="f-current">Current password</label>
                    <input type="password" id="f-current" name="current_password" class="form-control" autocomplete="current-password">
                    @error('current_password')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="row">
                    <div class="form-group col-sm-6 {{ $errors->has('password') ? 'has-error' : '' }}">
                        <label for="f-password">New password</label>
                        <input type="password" id="f-password" name="password" class="form-control" autocomplete="new-password" placeholder="At least 8 characters">
                        @error('password')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="f-password_confirmation">Repeat new password</label>
                        <input type="password" id="f-password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                </div>
                <p class="rp-help"><i class="bi bi-info-circle"></i> Leave the password fields empty to keep your current password.</p>
            </section>
        </div>
    </div>
    <div class="rf-actionbar">
        <a href="{{ url('welcome') }}" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Save</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#f-full_name').on('input', function () { $('#preview-name').text(this.value || 'Full Name'); });
    $('#f-images').on('change', function () { if (this.files[0]) $('#avatar-img').attr('src', URL.createObjectURL(this.files[0])); });
});
</script>
@endpush
