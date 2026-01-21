@extends('backend.layouts.app')

@section('title', 'Lead Automation')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Lead Automation</h1>
        <p class="mt-1 text-sm text-gray-600">Manage and automate your lead follow-up process</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Leads</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Converted</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['converted'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Hot Leads</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['hot'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">New Leads</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['new'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mb-6 flex space-x-3">
        <a href="{{ route('admin.lead-automation.broadcast-form') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition duration-300">
            📢 Send Broadcast
        </a>
        <a href="{{ route('admin.leads.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
            ➕ Add Lead
        </a>
    </div>

    <!-- Leads Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">All Leads</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Leads sorted by score (highest first)
            </p>
        </div>
        
        @if($leads->count() > 0)
            <ul class="divide-y divide-gray-200">
                @foreach($leads as $lead)
                    <li class="hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ strtoupper(substr($lead->first_name, 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <a href="{{ route('admin.lead-automation.show', $lead->id) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                                {{ $lead->first_name }} {{ $lead->last_name }}
                                            </a>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($lead->qualification === 'hot') bg-red-100 text-red-800
                                                @elseif($lead->qualification === 'warm') bg-yellow-100 text-yellow-800
                                                @else bg-blue-100 text-blue-800
                                                @endif">
                                                {{ ucfirst($lead->qualification) }}
                                            </span>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($lead->status === 'new') bg-green-100 text-green-800
                                                @elseif($lead->status === 'contacted') bg-blue-100 text-blue-800
                                                @elseif($lead->status === 'converted') bg-purple-100 text-purple-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($lead->status) }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                            <span>{{ $lead->email ?: $lead->phone ?: 'No contact info' }}</span>
                                            <span>•</span>
                                            <div class="text-sm text-gray-900">{{ \App\Helpers\PlatformHelper::getPlatformLabel($lead->platform) }}</div>
                                            <span>•</span>
                                            <span>{{ ucfirst(str_replace('_', ' ', $lead->source_type)) }}</span>
                                            <span>•</span>
                                            <span>Score: {{ $lead->score }}/100</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.lead-automation.show', $lead->id) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                                        View
                                    </a>
                                    <form action="{{ route('admin.lead-automation.rescore', $lead->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900 text-sm ml-3">
                                            Rescore
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No leads yet</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Get started by capturing leads from your social media accounts.
                </p>
                <div class="mt-6">
                    <a href="{{ route('admin.leads.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Add Your First Lead
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if($leads->hasPages())
        <div class="mt-6">
            {{ $leads->links() }}
        </div>
    @endif
</div>
@endsection
