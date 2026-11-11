@extends('auth.layout')

@section('title', 'Set New Password')

@section('card')
    <span class="icon-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
    </span>
    <h2 id="card-title">Set a new password</h2>
    <p class="lead">For <b>{{ $user->email }}</b>. This link works once and expires {{ $expires->diffForHumans() }}.</p>

    @include('auth._alerts')

    {{-- Action = URL bertanda tangan yang sama, jadi POST juga diverifikasi middleware "signed". --}}
    <form method="POST" action="{{ request()->fullUrl() }}" data-loading novalidate>
        @csrf
        <label for="password">New password</label>
        @include('auth._password-field', ['name' => 'password', 'autocomplete' => 'new-password', 'placeholder' => 'At least 8 characters', 'autofocus' => true])

        <label for="password_confirmation">Repeat new password</label>
        @include('auth._password-field', ['name' => 'password_confirmation', 'autocomplete' => 'new-password'])

        <button type="submit" class="btn">
            <span class="spinner" aria-hidden="true"></span>
            <span>Save new password</span>
        </button>
    </form>

    <a href="{{ route('login') }}" class="link back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to login
    </a>
@endsection
