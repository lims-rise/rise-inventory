@extends('layouts.app')

@section('title', 'Level Management')

@section('content')
@php
    $isEdit = $editing !== null;
    $v = fn (string $key, $default = null) => old($key, $editing->$key ?? $default);
@endphp

@include('config._head', [
    'icon' => 'bi-shield-lock', 'heading' => 'Level Management',
    'sub' => 'Each level decides which menus a user can open. Built-in levels cannot be deleted.',
    'crumb' => '<li>Level Management</li>',
])

<div class="row">
    <div class="col-lg-8">
        <div class="cf-levels">
            @foreach ($levels as $level)
                <div class="rp-card cf-level-card {{ $editing && (int) $editing->id_user_level === (int) $level->id_user_level ? 'cf-node-active' : '' }}">
                    <div class="cf-level-top">
                        <span class="cf-level-avatar">{{ strtoupper(mb_substr($level->nama_level, 0, 1)) }}</span>
                        <div>
                            <h3>{{ $level->nama_level }}</h3>
                            <p>Home page: <code>{{ $level->url ?: 'welcome' }}</code></p>
                        </div>
                        @if (in_array((int) $level->id_user_level, $locked, true))<span class="rf-badge rf-badge-muted"><i class="bi bi-lock"></i> Built-in</span>@endif
                    </div>
                    <div class="cf-level-stats">
                        <div><b>{{ $level->users_count }}</b><span>{{ \Illuminate\Support\Str::plural('user', $level->users_count) }}</span></div>
                        <div><b>{{ $level->menus_count }}</b><span>menus allowed</span></div>
                    </div>
                    <div class="cf-level-actions">
                        <a href="{{ route('userlevel.akses', $level->id_user_level) }}" class="btn btn-primary btn-sm"><i class="bi bi-ui-checks"></i> Manage access</a>
                        <a href="{{ route('userlevel.index', ['id' => $level->id_user_level]) }}" class="btn btn-default btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                        @unless (in_array((int) $level->id_user_level, $locked, true))
                            <button type="button" class="btn btn-default btn-sm cf-del" data-delete="{{ route('userlevel.destroy', $level->id_user_level) }}" data-name="{{ $level->nama_level }}"><i class="bi bi-trash3"></i></button>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ $isEdit ? route('userlevel.update') : route('userlevel.store') }}" class="rp-card rf-section cf-panel {{ $isEdit ? 'sf-panel-edit' : '' }}" novalidate>
            @csrf
            @if ($isEdit)<input type="hidden" name="id_user_level" value="{{ $editing->id_user_level }}">@endif
            <h3 class="rf-section-title"><i class="bi {{ $isEdit ? 'bi-pencil-square' : 'bi-plus-lg' }}"></i> {{ $isEdit ? 'Edit level' : 'New level' }}</h3>
            <div class="form-group {{ $errors->has('nama_level') ? 'has-error' : '' }}">
                <label for="f-nama_level">Level Name <span class="rp-req">*</span></label>
                <input type="text" id="f-nama_level" name="nama_level" class="form-control" value="{{ $v('nama_level') }}" maxlength="30" placeholder="e.g. Viewer">
                @error('nama_level')<span class="help-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group {{ $errors->has('url') ? 'has-error' : '' }}">
                <label for="f-url">Home page</label>
                <input type="text" id="f-url" name="url" class="form-control rf-mono" value="{{ $v('url') }}" maxlength="50" placeholder="welcome">
                <p class="rp-help"><i class="bi bi-info-circle"></i> Page opened after login, e.g. <code>welcome</code> or <code>staff/read</code>. It must be a menu this level can access.</p>
                @error('url')<span class="help-block">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-block sf-submit"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Save changes' : 'Add level' }}</button>
            @if ($isEdit)<a href="{{ route('userlevel.index') }}" class="btn btn-default btn-block">Cancel</a>@endif
        </form>
    </div>
</div>

@include('config._delete_modal', ['what' => 'level', 'note' => 'Its menu access is removed too.'])
@endsection
