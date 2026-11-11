@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')
@php
    $isEdit = $editing !== null;
    $v = fn (string $key, $default = null) => old($key, $editing->$key ?? $default);
    $row = function ($m, bool $child) use ($access, $editing) {
        return view('config._menu_row', ['m' => $m, 'child' => $child, 'levels' => $access->get($m->id_menu, collect()),
            'active' => $editing && (int) $editing->id_menu === (int) $m->id_menu])->render();
    };
@endphp

@include('config._head', [
    'icon' => 'bi-list-nested', 'heading' => 'Menu Management',
    'sub' => '<b>'.$total.'</b> menus. These drive the sidebar and what each level can open.',
    'crumb' => '<li>Menu Management</li>',
    'actions' => '<a href="'.e(route('userlevel.index')).'" class="btn btn-default"><i class="bi bi-shield-lock"></i> Level access</a>',
])

<div class="row">
    <div class="col-lg-8">
        <div class="rp-card">
            <div class="cf-tree">
                @foreach ($mains as $main)
                    {!! $row($main, false) !!}
                    @foreach ($children->get($main->id_menu, collect()) as $child)
                        {!! $row($child, true) !!}
                    @endforeach
                @endforeach
                @if ($orphans->isNotEmpty())
                    <div class="cf-tree-note"><i class="bi bi-exclamation-triangle"></i> Sub menus whose parent no longer exists</div>
                    @foreach ($orphans as $o){!! $row($o, true) !!}@endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ $isEdit ? route('kelolamenu.update') : route('kelolamenu.store') }}" class="rp-card rf-section cf-panel {{ $isEdit ? 'sf-panel-edit' : '' }}" novalidate>
            @csrf
            @if ($isEdit)<input type="hidden" name="id_menu" value="{{ $editing->id_menu }}">@endif
            <h3 class="rf-section-title"><i class="bi {{ $isEdit ? 'bi-pencil-square' : 'bi-plus-lg' }}"></i> {{ $isEdit ? 'Edit menu' : 'New menu' }}</h3>

            <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                <label for="f-title">Title <span class="rp-req">*</span></label>
                <input type="text" id="f-title" name="title" class="form-control" value="{{ $v('title') }}" maxlength="50" placeholder="e.g. INVENTORY LIST">
                @error('title')<span class="help-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group {{ $errors->has('url') ? 'has-error' : '' }}">
                <label for="f-url">URL <span class="rp-req">*</span></label>
                <input type="text" id="f-url" name="url" class="form-control rf-mono" value="{{ $v('url') }}" maxlength="30" placeholder="item_master">
                <p class="rp-help"><i class="bi bi-info-circle"></i> Use <code>#</code> for a group that only holds sub menus.</p>
                @error('url')<span class="help-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group {{ $errors->has('icon') ? 'has-error' : '' }}">
                <label for="f-icon">Icon <span class="rp-req">*</span></label>
                <div class="cf-icon-input">
                    <span class="cf-icon-preview"><i class="{{ $v('icon', 'fa fa-circle-o') }}" id="icon-preview"></i></span>
                    <input type="text" id="f-icon" name="icon" class="form-control rf-mono" value="{{ $v('icon') }}" maxlength="30" placeholder="fa fa-server">
                </div>
                <p class="rp-help"><i class="bi bi-info-circle"></i> Font Awesome 4 class, as used in the sidebar.</p>
                @error('icon')<span class="help-block">{{ $message }}</span>@enderror
            </div>
            <div class="row">
                <div class="form-group col-xs-7 {{ $errors->has('is_main_menu') ? 'has-error' : '' }}">
                    <label for="f-parent">Parent</label>
                    <select id="f-parent" name="is_main_menu" class="form-control">
                        <option value="0">— Main menu —</option>
                        @foreach ($parents as $id => $title)
                            <option value="{{ $id }}" @selected((string) $v('is_main_menu', 0) === (string) $id)>{{ strtoupper($title) }}</option>
                        @endforeach
                    </select>
                    @error('is_main_menu')<span class="help-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group col-xs-5 {{ $errors->has('_index') ? 'has-error' : '' }}">
                    <label for="f-index">Order</label>
                    <input type="number" id="f-index" name="_index" class="form-control" value="{{ $v('_index') }}" min="0" max="999" placeholder="—">
                    @error('_index')<span class="help-block">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <div class="rf-toggle">
                    <label><input type="radio" name="is_aktif" value="y" @checked($v('is_aktif', 'y') === 'y')><span><i class="bi bi-eye"></i> Shown</span></label>
                    <label><input type="radio" name="is_aktif" value="n" @checked($v('is_aktif', 'y') === 'n')><span><i class="bi bi-eye-slash"></i> Hidden</span></label>
                </div>
                <p class="rp-help"><i class="bi bi-info-circle"></i> Hidden menus are not shown in the sidebar but still grant access to their URL (e.g. <code>staff/read</code>).</p>
            </div>

            <button type="submit" class="btn btn-primary btn-block sf-submit"><i class="bi bi-check2-circle"></i> {{ $isEdit ? 'Save changes' : 'Add menu' }}</button>
            @if ($isEdit)<a href="{{ route('kelolamenu.index') }}" class="btn btn-default btn-block">Cancel</a>@endif
        </form>
    </div>
</div>

@include('config._delete_modal', ['what' => 'menu', 'note' => 'Level access for this menu is removed too.'])
@endsection

@push('scripts')
<script>
$(function () {
    $('#f-icon').on('input', function () { $('#icon-preview').attr('class', this.value || 'fa fa-circle-o'); });
});
</script>
@endpush
