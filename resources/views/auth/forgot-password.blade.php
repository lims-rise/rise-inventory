@extends('auth.layout')

@section('title', 'Forgot Password')

@section('card')
    <span class="icon-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/></svg>
    </span>
    <h2 id="card-title">Forgot your password?</h2>
    <p class="lead">Enter the email you use to log in. We'll send you a link to set a new password.</p>

    @include('auth._alerts')

    <form method="POST" action="{{ route('password.email') }}" data-loading novalidate>
        @csrf
        <label for="email">Email</label>
        <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
            <input type="text" id="email" name="email" value="{{ old('email') }}"
                   placeholder="name@email.com" autocomplete="username" autofocus required>
        </div>

        <button type="submit" class="btn">
            <span class="spinner" aria-hidden="true"></span>
            <span>Send reset link</span>
        </button>
    </form>

    <a href="{{ route('login') }}" class="link back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to login
    </a>
@endsection
