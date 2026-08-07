@extends('admin::layouts.guest')

@section('title', 'Sign up')

@section('content')
    <h1 class="auth-title">Sign up</h1>
    <p class="auth-lead">Create a new admin account.</p>

    <form method="POST" action="{{ route('admin.signup.submit') }}">
        @csrf

        <div class="form-field">
            <label class="form-label" for="name">Name</label>
            <input
                class="form-input"
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                autocomplete="name"
                required
                autofocus
            >
            @error('name')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-input"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
            >
            @error('email')
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
                autocomplete="new-password"
                required
            >
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input
                class="form-input"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
            >
        </div>

        <button class="btn" type="submit">Create account</button>
    </form>

    <p class="auth-footer">
        Already have an account?
        <a href="{{ route('admin.login') }}">Login</a>
    </p>
@endsection
