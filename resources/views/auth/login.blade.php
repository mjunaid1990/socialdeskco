@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center mb-6">Login</h2>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                <input id="email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md @error('email') border-red-500 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                
                @error('email')
                    <span class="text-red-500 text-sm mt-1" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input id="password" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md @error('password') border-red-500 @enderror" name="password" required autocomplete="current-password">
                
                @error('password')
                    <span class="text-red-500 text-sm mt-1" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-4">
                <div class="flex items-center">
                    <input id="remember" type="checkbox" class="mr-2" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember" class="text-gray-700 text-sm">Remember Me</label>
                </div>
            </div>

            <div class="mb-4">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-300">
                    Login
                </button>
            </div>

            <div class="text-center">
                @if (Route::has('password.request'))
                    <a class="text-blue-600 text-sm hover:text-blue-800" href="{{ route('password.request') }}">
                        Forgot Your Password?
                    </a>
                @endif
                <p class="mt-4">
                    Don't have an account? <a class="text-blue-600 hover:text-blue-800" href="{{ route('register') }}">Register</a>
                </p>
            </div>
        </form>
    </div>
</div>
@endsection