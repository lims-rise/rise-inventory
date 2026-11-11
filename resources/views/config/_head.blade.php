{{-- Judul halaman Config: $icon, $heading, $sub (HTML aman), $crumb, $actions (HTML) --}}
<div class="rp-head">
    <div class="rp-head-title">
        <span class="rp-head-icon"><i class="bi {{ $icon }}"></i></span>
        <div>
            <ol class="rp-crumb">
                <li><a href="{{ url('welcome') }}"><i class="bi bi-house-door"></i></a></li>
                <li>Config</li>
                @isset($crumb){!! $crumb !!}@endisset
            </ol>
            <h1>{{ $heading }}</h1>
            @isset($sub)<p>{!! $sub !!}</p>@endisset
        </div>
    </div>
    @isset($actions)<div class="rp-head-actions">{!! $actions !!}</div>@endisset
</div>
