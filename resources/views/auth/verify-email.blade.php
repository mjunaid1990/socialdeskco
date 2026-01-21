@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md text-center">
        <h2 class="text-2xl font-bold mb-6">Verify Your Email Address</h2>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 text-green-600">
                A new verification link has been sent to the email address you provided during registration.
            </div>
        @endif

        <p class="mb-6">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div class="mb-4">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-300">
                    Resend Verification Email
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Logout
            </button>
        </form>
    </div>
</div>
@endsection