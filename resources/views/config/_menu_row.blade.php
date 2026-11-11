{{-- Satu baris pohon menu. $m (Menu), $child (bool), $levels (nama level yang punya akses), $active --}}
<div class="cf-node {{ $child ? 'cf-node-child' : '' }} {{ $m->is_aktif !== 'y' ? 'cf-node-hidden' : '' }} {{ $active ? 'cf-node-active' : '' }}">
    <span class="cf-node-icon"><i class="{{ $m->icon }}"></i></span>
    <div class="cf-node-main">
        <b>{{ $m->title }}</b>
        <span class="cf-node-meta">
            <code>{{ $m->url }}</code>
            @if ($m->_index !== null)<span title="Order">#{{ $m->_index }}</span>@endif
            @if ($m->is_aktif !== 'y')<span class="rf-badge rf-badge-muted">Hidden</span>@endif
        </span>
    </div>
    <div class="cf-node-levels">
        @forelse ($levels as $name)
            <span class="cf-level">{{ $name }}</span>
        @empty
            <span class="text-muted cf-level-none">No level</span>
        @endforelse
    </div>
    <div class="rp-actions">
        <a href="{{ route('kelolamenu.index', ['id' => $m->id_menu]) }}" class="rp-act rp-act-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>
        <button type="button" class="rp-act rp-act-del" title="Delete" data-delete="{{ route('kelolamenu.destroy', $m->id_menu) }}" data-name="{{ $m->title }}"><i class="bi bi-trash3"></i></button>
    </div>
</div>
