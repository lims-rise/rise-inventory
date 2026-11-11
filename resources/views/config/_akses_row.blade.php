{{-- Satu baris hak akses. $m, $child, $on (bool), $lock (bool) --}}
<label class="cf-node {{ $child ? 'cf-node-child' : '' }} {{ $m->is_aktif !== 'y' ? 'cf-node-hidden' : '' }}">
    <span class="cf-node-icon"><i class="{{ $m->icon }}"></i></span>
    <span class="cf-node-main">
        <b>{{ $m->title }}</b>
        <span class="cf-node-meta"><code>{{ $m->url }}</code>
            @if ($m->is_aktif !== 'y')<span class="rf-badge rf-badge-muted">Hidden menu</span>@endif
            @if ($lock)<span class="rf-badge rf-badge-muted"><i class="bi bi-lock"></i> Needed by your own level</span>@endif
        </span>
    </span>
    <span class="cf-switch">
        <input type="checkbox" value="{{ $m->id_menu }}" @checked($on) @disabled($lock && $on) aria-label="Allow {{ $m->title }}">
        <span></span>
    </span>
</label>
