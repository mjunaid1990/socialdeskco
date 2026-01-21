@extends('backend.layouts.app')

@section('title', 'Business Social Media Settings')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Business Social Media Settings</h1>
                    <p class="mt-1 text-sm text-gray-600">Configure social media platform credentials for {{ $business->name }}</p>
                </div>
                <a href="{{ route('tenant.businesses.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Back to My Businesses
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Social Media Platform Credentials</h2>
            
            <form id="tenant-business-social-media-settings-form" action="{{ route('tenant.businesses.update-social-media-settings', $business) }}" method="POST">
                @csrf
                @method('PUT')
                
                @php
                    $allGroups = App\Helpers\SettingsHelper::getSocialMediaPlatformGroups();
                    $lastGroup = end($allGroups);
                @endphp
                <div class="space-y-8">
                    @foreach(App\Helpers\SettingsHelper::getGroups() as $group)
                        @if(in_array($group, App\Helpers\SettingsHelper::getSocialMediaPlatformGroups()))
                        <div class="{{ $group !== $lastGroup ? 'border-b border-gray-200' : '' }} pb-6">
                            <h3 class="text-md font-semibold text-gray-800 mt-4 mb-3 capitalize">{{ $group }} Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($allSettingsConfig as $key => $config)
                                    @if(strpos($key, $group) === 0)
                                    <div class="{{ in_array($config['type'], ['textarea', 'checkbox']) ? 'md:col-span-2' : '' }}">
                                        <label for="{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $config['label'] }}</label>
                                        @if($config['type'] === 'text' || $config['type'] === 'email' || $config['type'] === 'number')
                                            <input type="{{ $config['type'] }}" id="{{ $key }}" name="{{ $key }}" 
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                placeholder="{{ $config['placeholder'] ?? '' }}" 
                                                value="{{ old($key, $settings[$key] ?? '') }}">
                                        @elseif($config['type'] === 'password')
                                            <input type="{{ $config['type'] }}" id="{{ $key }}" name="{{ $key }}" 
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                placeholder="{{ $config['placeholder'] ?? '' }}">
                                        @elseif($config['type'] === 'textarea')
                                            <textarea id="{{ $key }}" name="{{ $key }}" rows="4"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                placeholder="{{ $config['placeholder'] ?? '' }}">{{ old($key, $settings[$key] ?? '') }}</textarea>
                                        @elseif($config['type'] === 'select')
                                            <select id="{{ $key }}" name="{{ $key }}" 
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select an option</option>
                                                @foreach($config['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" {{ (old($key, $settings[$key] ?? '') == $optionValue) ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($config['type'] === 'checkbox')
                                            <div class="flex items-start">
                                                <div class="flex items-center h-5">
                                                    <input id="{{ $key }}" name="{{ $key }}" type="checkbox" 
                                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded" 
                                                        {{ (old($key, $settings[$key] ?? '') == '1' || old($key, $settings[$key] ?? '') == 'on') ? 'checked' : '' }}>
                                                </div>
                                                <div class="ml-3 text-sm">
                                                    <label for="{{ $key }}" class="font-medium text-gray-700">{{ $config['label'] }}</label>
                                                </div>
                                            </div>
                                        @endif
                                        <p class="mt-1 text-sm text-gray-500">{{ $config['description'] }}</p>
                                        @error($key)
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection