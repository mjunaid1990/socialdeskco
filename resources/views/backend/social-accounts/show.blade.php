@extends('backend.layouts.app')

@section('title', 'View Social Account')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Social Account Details</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $socialAccount->account_name }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.social-accounts.edit', $socialAccount->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                Edit Account
            </a>
            <a href="{{ route('admin.social-accounts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                Back to Accounts
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Account Information</h3>
                <div class="mt-4 space-y-4">
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Platform</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 capitalize">{{ $socialAccount->platform }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Account Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $socialAccount->account_name }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Account ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $socialAccount->account_id }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $socialAccount->is_active ? 'green' : 'red' }}-100 text-{{ $socialAccount->is_active ? 'green' : 'red' }}-800">
                                {{ $socialAccount->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $socialAccount->created_at->format('M d, Y h:i A') }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Last Sync</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $socialAccount->last_sync_at ? $socialAccount->last_sync_at->format('M d, Y h:i A') : 'Never' }}</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection