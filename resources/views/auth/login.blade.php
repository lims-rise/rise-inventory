@extends('auth.layout')

@section('title', 'Login')

@section('card')
    <h2 id="card-title">RISE | Asset DB Management</h2>
    <p class="lead">Login with your account to continue.</p>

    @include('auth._alerts')

    <form method="POST" action="{{ route('login.attempt') }}" data-loading novalidate>
        @csrf

        <label for="email">Email</label>
        <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
            <input type="text" id="email" name="email" value="{{ old('email') }}"
                   placeholder="name@email.com" autocomplete="username" autofocus required>
        </div>

        <div class="row-between">
            <label for="password">Password</label>
            <a href="{{ route('password.request') }}" class="link">Forgot password?</a>
        </div>
        @include('auth._password-field', ['name' => 'password', 'autocomplete' => 'current-password'])

        <button type="submit" class="btn">
            <span class="spinner" aria-hidden="true"></span>
            <span>Login</span>
        </button>
    </form>
@endsection
