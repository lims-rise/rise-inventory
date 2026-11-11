@extends('layouts.app')

@section('title', 'Access · '.$level->nama_level)

@section('content')
@include('config._head', [
    'icon' => 'bi-ui-checks', 'heading' => 'Access for '.$level->nama_level,
    'sub' => 'Switch a menu on to let this level open it. Changes are saved immediately.',
    'crumb' => '<li><a href="'.e(route('userlevel.index')).'">Level Management</a></li><li>Access</li>',
    'actions' => '<a href="'.e(route('userlevel.index')).'" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>',
])

<div class="rp-card">
    <div class="cf-akses-summary">
        <b id="granted-count">{{ count($granted) }}</b> of {{ $mains->count() + $children->flatten(1)->count() }} menus allowed
        <span class="text-muted">· Sub menus only show in the sidebar when their group is allowed too.</span>
    </div>
    <div class="cf-tree">
        @php
            $node = function ($m, bool $child) use ($granted, $protected) {
                $on = in_array((int) $m->id_menu, $granted, true);
                $lock = in_array((int) $m->id_menu, $protected, true);

                return view('config._akses_row', compact('m', 'child', 'on', 'lock'))->render();
            };
        @endphp
        @foreach ($mains as $main)
            {!! $node($main, false) !!}
            @foreach ($children->get($main->id_menu, collect()) as $child)
                {!! $node($child, true) !!}
            @endforeach
        @endforeach
        @foreach ($orphans as $o){!! $node($o, true) !!}@endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var url = @json(route('userlevel.toggle', $level->id_user_level));

    $('.cf-switch input').on('change', function () {
        var $input = $(this);
        var granted = $input.is(':checked');
        var $node = $input.closest('.cf-node').addClass('cf-saving');

        $.ajax({ url: url, type: 'POST', data: { id_menu: $input.val(), granted: granted ? 1 : 0 }, dataType: 'json', global: false })
            .done(function () {
                var $count = $('#granted-count');
                $count.text(parseInt($count.text(), 10) + (granted ? 1 : -1));
                $node.addClass('cf-saved');
                setTimeout(function () { $node.removeClass('cf-saved'); }, 900);
            })
            .fail(function (x) {
                $input.prop('checked', !granted);
                alert((x.responseJSON && x.responseJSON.message) || 'Could not save. Please try again.');
            })
            .always(function () { $node.removeClass('cf-saving'); });
    });
});
</script>
@endpush
