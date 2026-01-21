@extends('backend.layouts.app')

@section('title', 'Edit Social Account')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Social Account</h1>
        <p class="mt-1 text-sm text-gray-600">Update your connected social media account</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.social-accounts.update', $socialAccount->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('backend.social-accounts._form')
            
            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('admin.social-accounts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                    Update Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection