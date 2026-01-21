@extends('backend.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage your application settings</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-0">
                <a href="{{ route('admin.settings.index') }}" class="-mb-px py-4 px-6 text-center border-b-2 font-medium text-sm {{ request()->routeIs('admin.settings.index') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    General
                </a>
                <a href="{{ route('admin.settings.social-media') }}" class="-mb-px py-4 px-6 text-center border-b-2 font-medium text-sm {{ request()->routeIs('admin.settings.social-media') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Social Media
                </a>
            </nav>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">General Settings</h2>
            
            <form id="general-settings-form" enctype="multipart/form-data">
                @csrf
                <div class="space-y-8">
                    @php
                        $allGroups = App\Helpers\SettingsHelper::getGroups();
                        $filteredGroups = array_filter($allGroups, function($group) {
                            return !in_array($group, ['facebook', 'instagram', 'linkedin', 'x', 'whatsapp', 'tiktok', 'youtube', 'snapchat', 'pinterest', 'reddit', 'telegram', 'discord', 'tumblr', 'flickr', 'vimeo', 'twitch']);
                        });
                        $lastGroup = end($filteredGroups);
                    @endphp
                    @foreach($filteredGroups as $group)
                        <div class="{{ $group !== $lastGroup ? 'border-b border-gray-200' : '' }} pb-6">
                            <h3 class="text-md font-semibold text-gray-800 mt-4 mb-3 capitalize">{{ $group }} Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach(App\Helpers\SettingsHelper::getSettingsByGroup($group) as $key => $config)
                                    <div class="{{ in_array($config['type'], ['textarea', 'checkbox', 'file']) ? 'md:col-span-2' : '' }}">
                                        <label for="{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $config['label'] }}</label>
                                        @if($config['type'] === 'text' || $config['type'] === 'email' || $config['type'] === 'number')
                                            <input type="{{ $config['type'] }}" id="{{ $key }}" name="{{ $key }}" 
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                placeholder="{{ $config['placeholder'] ?? '' }}" 
                                                value="{{ old($key, $settings[$key]->setting_value ?? '') }}">
                                        @elseif($config['type'] === 'password')
                                            <input type="{{ $config['type'] }}" id="{{ $key }}" name="{{ $key }}" 
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                placeholder="{{ $config['placeholder'] ?? '' }}">
                                        @elseif($config['type'] === 'file')
                                            <div class="space-y-2">
                                                <input type="file" id="{{ $key }}_file" name="{{ $key }}_file" 
                                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    accept="image/*"
                                                    {{ $key === 'app_favicon' ? 'max-size="1048576"' : 'max-size="2097152"' }}>
                                                @if($settings[$key]->setting_value ?? '')
                                                    <div class="flex items-center space-x-2 mt-2">
                                                        <div class="w-24 h-24 border-2 border-gray-300 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center">
                                                            <img src="{{ $settings[$key]->setting_value }}" alt="{{ $config['label'] }}" 
                                                                class="max-w-full max-h-full object-contain">
                                                        </div>
                                                        <button type="button" onclick="removeFile('{{ $key }}')" 
                                                            class="text-red-600 hover:text-red-800 text-sm">
                                                            Remove
                                                        </button>
                                                    </div>
                                                @endif
                                                <input type="hidden" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $settings[$key]->setting_value ?? '') }}">
                                            </div>
                                        @elseif($config['type'] === 'textarea')
                                            <textarea id="{{ $key }}" name="{{ $key }}" rows="4"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                placeholder="{{ $config['placeholder'] ?? '' }}">{{ old($key, $settings[$key]->setting_value ?? '') }}</textarea>
                                        @elseif($config['type'] === 'select')
                                            <select id="{{ $key }}" name="{{ $key }}" 
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select an option</option>
                                                @foreach($config['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" {{ (old($key, $settings[$key]->setting_value ?? '') == $optionValue) ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($config['type'] === 'checkbox')
                                            <div class="flex items-start">
                                                <div class="flex items-center h-5">
                                                    <input id="{{ $key }}" name="{{ $key }}" type="checkbox" 
                                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded" 
                                                        {{ (old($key, $settings[$key]->setting_value ?? '') == '1' || old($key, $settings[$key]->setting_value ?? '') == 'on') ? 'checked' : '' }}>
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
                                @endforeach
                            </div>
                        </div>
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

<script>
    function removeFile(key) {
        document.getElementById(key).value = '';
        const fileInput = document.getElementById(key + '_file');
        const preview = fileInput.parentElement.querySelector('img');
        if (preview) {
            preview.style.display = 'none';
        }
        fileInput.value = '';
    }

    // Handle file input changes
    document.addEventListener('change', function(e) {
        if (e.target.type === 'file' && e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            const key = e.target.id.replace('_file', '');
            const reader = new FileReader();
            
            reader.onload = function(event) {
                const hiddenInput = document.getElementById(key);
                hiddenInput.value = event.target.result;
                
                // Show preview
                const preview = e.target.parentElement.querySelector('img');
                if (preview) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
            };
            
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('general-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Collect form data
        const formData = new FormData(this);
        const settings = {};
        
        // Handle file uploads separately
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        for (let [key, value] of formData.entries()) {
            if (key !== '_token' && key !== '_method' && !key.endsWith('_file')) {
                settings[key] = value;
            }
        }
        
        // Process file uploads
        fileInputs.forEach(input => {
            if (input.files && input.files[0]) {
                const settingKey = input.id.replace('_file', '');
                const hiddenInput = document.getElementById(settingKey);
                if (hiddenInput && hiddenInput.value) {
                    settings[settingKey] = hiddenInput.value;
                }
            }
        });
        
        // Send each setting individually via AJAX
        for (const [key, value] of Object.entries(settings)) {
            fetch(`/admin/settings/key/${key}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ setting_value: value })
            });
        }
        
        alert('Settings saved successfully!');
        window.location.reload();
    });
</script>

@endsection