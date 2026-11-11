@extends('auth.layout')

@section('title', $heading)

@section('card')
    @php
        // Jangan sampai halaman error ikut error (mis. database mati saat 500).
        try {
            $home = auth()->check() ? auth()->user()->homeUrl() : route('login');
            $homeLabel = auth()->check() ? 'Back to home' : 'Go to login';
        } catch (\Throwable $e) {
            $home = url('/');
            $homeLabel = 'Back to home';
        }
    @endphp
    <span class="icon-badge" style="background:{{ $tint ?? '#eaf2ff' }};color:{{ $color ?? 'var(--blue)' }}">
        {!! $icon !!}
    </span>
    <p class="lead" style="margin:0 0 4px;font-weight:700;letter-spacing:.12em;">ERROR {{ $code }}</p>
    <h2 id="card-title">{{ $heading }}</h2>
    <p class="lead">{{ $text }}</p>
    <a href="{{ $action ?? $home }}" class="btn" style="text-decoration:none">{{ $actionLabel ?? $homeLabel }}</a>
    @if (empty($action))
        <a href="javascript:history.back()" class="link back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Previous page
        </a>
    @endif
@endsection
