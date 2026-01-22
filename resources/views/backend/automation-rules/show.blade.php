@extends('backend.layouts.app')

@section('title', 'Automation Rule Details')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Automation Rule Details</h1>
                    <p class="mt-1 text-sm text-gray-600">View automation rule configuration</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.automation-rules.edit', $automationRule->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                        Edit
                    </a>
                    <a href="{{ route('admin.automation-rules.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                        Back to Rules
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Rule Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $automationRule->id }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $automationRule->name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trigger Type</label>
                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $automationRule->trigger_type)) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Action Type</label>
                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $automationRule->action_type)) }}</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Configuration</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trigger Conditions</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $automationRule->trigger_conditions ? json_encode($automationRule->trigger_conditions) : 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Enabled</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($automationRule->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        No
                                    </span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $automationRule->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Updated At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $automationRule->updated_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection