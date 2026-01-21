@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center mb-6">Confirm Password</h2>

        <p class="text-center mb-6">
            Please confirm your password before continuing.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input id="password" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md @error('password') border-red-500 @enderror" name="password" required autocomplete="current-password" autofocus>
                
                @error('password')
                    <span class="text-red-500 text-sm mt-1" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-4">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-300">
                    Confirm Password
                </button>
            </div>

            @if (Route::has('password.request'))
                <div class="text-center">
                    <a class="text-blue-600 text-sm hover:text-blue-800" href="{{ route('password.request') }}">
                        Forgot Your Password?
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection