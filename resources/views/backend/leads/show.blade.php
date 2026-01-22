@extends('backend.layouts.app')

@section('title', 'View Lead')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lead Details</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $lead->first_name }} {{ $lead->last_name }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.leads.edit', $lead->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                Edit Lead
            </a>
            <a href="{{ route('admin.leads.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                Back to Leads
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Lead Information</h3>
                <div class="mt-4 space-y-4">
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->first_name }} {{ $lead->last_name }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->email ?: 'Not provided' }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->phone ?: 'Not provided' }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Platform</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 capitalize">{{ $lead->platform }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Source Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst(str_replace('_', ' ', $lead->source_type)) }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $lead->status === 'new' ? 'blue' : ($lead->status === 'contacted' ? 'yellow' : ($lead->status === 'converted' ? 'green' : 'red')) }}-100 text-{{ $lead->status === 'new' ? 'blue' : ($lead->status === 'contacted' ? 'yellow' : ($lead->status === 'converted' ? 'green' : 'red')) }}-800">
                                {{ ucfirst($lead->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Social Account</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->socialAccount->account_name ?? 'N/A' }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Platform Lead ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->platform_lead_id }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Message</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->message ?: 'No message' }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Custom Fields</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if($lead->custom_fields)
                                <pre class="text-xs bg-gray-100 p-2 rounded">{{ json_encode($lead->custom_fields, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                No custom fields
                            @endif
                        </dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->created_at->format('M d, Y h:i A') }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->updated_at->format('M d, Y h:i A') }}</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection