{{-- Judul halaman master: ikon, breadcrumb, judul, dan tombol aksi (slot $actions) --}}
<div class="rp-head">
    <div class="rp-head-title">
        <span class="rp-head-icon"><i class="bi {{ $resource->icon }}"></i></span>
        <div>
            <ol class="rp-crumb">
                <li><a href="{{ url('welcome') }}"><i class="bi bi-house-door"></i></a></li>
                <li>{{ $resource->group }}</li>
                <li><a href="{{ $resource->route('index') }}">{{ $resource->title }}</a></li>
                @isset($crumb)<li>{{ $crumb }}</li>@endisset
            </ol>
            <h1>{{ $heading }}</h1>
            @isset($sub)<p>{{ $sub }}</p>@endisset
        </div>
    </div>
    @isset($actions)
        <div class="rp-head-actions">{!! $actions !!}</div>
    @endisset
</div>
