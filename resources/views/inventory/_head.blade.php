{{-- Judul halaman inventaris: $heading, $sub (opsional, boleh HTML aman), $crumb (opsional), $actions (HTML) --}}
<div class="rp-head">
    <div class="rp-head-title">
        <span class="rp-head-icon"><i class="bi bi-box-seam"></i></span>
        <div>
            <ol class="rp-crumb">
                <li><a href="{{ url('welcome') }}"><i class="bi bi-house-door"></i></a></li>
                <li><a href="{{ route('item_master.index') }}">Inventory</a></li>
                @isset($crumb)<li>{{ $crumb }}</li>@endisset
            </ol>
            <h1>{{ $heading }}</h1>
            @isset($sub)<p>{!! $sub !!}</p>@endisset
        </div>
    </div>
    @isset($actions)
        <div class="rp-head-actions">{!! $actions !!}</div>
    @endisset
</div>
