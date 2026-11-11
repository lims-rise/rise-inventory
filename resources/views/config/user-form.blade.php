@extends('layouts.app')

@section('title', $user ? 'Edit User' : 'New User')

@section('content')
@php
    $isEdit = $user !== null;
    $v = fn (string $key, $default = null) => old($key, $user?->$key ?? $default);
@endphp

@include('config._head', [
    'icon' => 'bi-person-gear', 'heading' => $isEdit ? 'Edit User' : 'New User',
    'sub' => $isEdit ? e($user->email) : 'Create an account that can log in to the inventory.',
    'crumb' => '<li><a href="'.e(route('user.index')).'">Users Management</a></li><li>'.($isEdit ? 'Edit' : 'New').'</li>',
])

<form method="POST" action="{{ $isEdit ? route('user.update') : route('user.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($isEdit)<input type="hidden" name="id_users" value="{{ $user->id_users }}">@endif

    <div class="row">
        <div class="col-lg-4">
            <div class="rp-card sf-panel">
                <label class="sf-avatar" for="f-images" title="Change photo">
                    <img src="{{ $user ? $user->avatarUrl() : asset('images/avatar-default.svg') }}" alt="" id="avatar-img">
                    <span class="sf-avatar-cam"><i class="bi bi-camera"></i></span>
                </label>
                <input type="file" id="f-images" name="images" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                <h3 class="sf-preview-name" id="preview-name">{{ $v('full_name') ?: 'Full Name' }}</h3>
                <p class="sf-preview-dept">{{ $v('email') ?: 'email' }}</p>
                @error('images')<p class="text-danger">{{ $message }}</p>@enderror
                <p class="rp-help" style="text-align:center"><i class="bi bi-info-circle"></i> Click the photo to choose a picture (max 2 MB).</p>
            </div>
        </div>

        <div class="col-lg-8">
            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-person"></i> Account</h3>
                <div class="row">
                    <div class="form-group col-sm-6 {{ $errors->has('full_name') ? 'has-error' : '' }}">
                        <label for="f-full_name">Full Name <span class="rp-req">*</span></label>
                        <input type="text" id="f-full_name" name="full_name" class="form-control" value="{{ $v('full_name') }}" maxlength="50">
                        @error('full_name')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-sm-6 {{ $errors->has('email') ? 'has-error' : '' }}">
                        <label for="f-email">Email (used to log in) <span class="rp-req">*</span></label>
                        <input type="text" id="f-email" name="email" class="form-control" value="{{ $v('email') }}" maxlength="50" autocomplete="off">
                        @error('email')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6 {{ $errors->has('password') ? 'has-error' : '' }}">
                        <label for="f-password">{{ $isEdit ? 'New password' : 'Password' }} @unless($isEdit)<span class="rp-req">*</span>@endunless</label>
                        <input type="password" id="f-password" name="password" class="form-control" autocomplete="new-password"
                               placeholder="{{ $isEdit ? 'Leave empty to keep the current password' : 'At least 8 characters' }}">
                        @error('password')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="f-password_confirmation">Repeat password</label>
                        <input type="password" id="f-password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                </div>
            </section>

            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-shield-lock"></i> Access</h3>
                <div class="row">
                    <div class="form-group col-sm-6 {{ $errors->has('id_user_level') ? 'has-error' : '' }}">
                        <label for="f-level">Level <span class="rp-req">*</span></label>
                        <select id="f-level" name="id_user_level" class="form-control" @disabled($isSelf)>
                            @foreach ($levels as $id => $name)
                                <option value="{{ $id }}" @selected((string) $v('id_user_level', 3) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @if ($isSelf)<input type="hidden" name="id_user_level" value="{{ $user->id_user_level }}">
                            <p class="rp-help"><i class="bi bi-lock"></i> You cannot change your own level.</p>@endif
                        @error('id_user_level')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-sm-6">
                        <label>Status</label>
                        <div class="rf-toggle">
                            <label><input type="radio" name="is_aktif" value="y" @checked($v('is_aktif', 'y') === 'y')><span><i class="bi bi-check-circle"></i> Active</span></label>
                            <label><input type="radio" name="is_aktif" value="n" @checked($v('is_aktif', 'y') === 'n') @disabled($isSelf)><span><i class="bi bi-slash-circle"></i> Inactive</span></label>
                        </div>
                        <p class="rp-help"><i class="bi bi-info-circle"></i> Inactive users cannot log in.</p>
                    </div>
                </div>
                <div class="row">
                    @include('inventory._select', ['name' => 'location_id', 'label' => 'Default Location', 'options' => $locations,
                        'value' => $v('location_id', auth()->user()->location_id), 'required' => true, 'col' => 'col-sm-6'])
                    <div class="form-group col-sm-6 {{ $errors->has('staff_id') ? 'has-error' : '' }}">
                        <label for="f-staff_id">Link to Staff</label>
                        <select id="f-staff_id" name="staff_id" class="form-control select2" style="width:100%" data-placeholder="Not linked" data-allow-clear="true">
                            <option value=""></option>
                            @foreach ($staff as $group => $people)
                                <optgroup label="{{ $group }}">
                                    @foreach ($people as $p)
                                        <option value="{{ $p->id }}" @selected((string) $v('staff_id') === (string) $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="rp-help"><i class="bi bi-info-circle"></i> Staff-level users land on this staff profile after login.</p>
                        @error('staff_id')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="rf-actionbar">
        <a href="{{ route('user.index') }}" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Save changes' : 'Create user' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#f-full_name').on('input', function () { $('#preview-name').text(this.value || 'Full Name'); });
    $('#f-images').on('change', function () {
        var file = this.files[0];
        if (file) $('#avatar-img').attr('src', URL.createObjectURL(file));
    });
});
</script>
@endpush
