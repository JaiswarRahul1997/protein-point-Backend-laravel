@extends('admin::layouts.guest')

@section('title', 'Login')

@section('content')
    <h1 class="auth-title">Login</h1>
    <p class="auth-lead">Sign in to access the admin dashboard.</p>

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf

        <div class="form-field">
            <label class="form-label" for="username">Username</label>
            <input
                class="form-input"
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                autocomplete="username"
                required
                autofocus
            >
            @error('username')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="password">Password</label>
            <input
                class="form-input"
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button class="btn" type="submit">Login</button>
    </form>

    <p class="auth-footer">
        Don't have an account?
        <a href="{{ route('admin.signup') }}">Sign up</a>
    </p>
@endsection
