@extends('backend.layouts.app')

@section('title', 'Engagement Details')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Engagement Details</h1>
                    <p class="mt-1 text-sm text-gray-600">View engagement information</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.engagements.edit', $engagement->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                        Edit
                    </a>
                    <a href="{{ route('admin.engagements.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                        Back to Engagements
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Engagement Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $engagement->id }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Platform</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $engagement->platform ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Engagement Type</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $engagement->engagement_type ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content</label>
                            <p class="mt-1 text-sm text-gray-900">{{ Str::limit($engagement->content ?? 'N/A', 100) }}</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Additional Details</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $engagement->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Updated At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $engagement->updated_at->format('M j, Y g:i A') }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Automated Response</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($engagement->is_automated_response)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        No
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection