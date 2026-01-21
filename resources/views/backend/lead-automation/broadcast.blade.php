@extends('backend.layouts.app')

@section('title', 'Send WhatsApp Broadcast')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Send WhatsApp Broadcast</h1>
                <p class="mt-1 text-sm text-gray-600">Send a message to multiple leads at once</p>
            </div>
            <a href="{{ route('admin.lead-automation.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                Back to Leads
            </a>
        </div>
    </div>

    <!-- Lead Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $leadCounts['total'] }}</div>
            <div class="text-sm text-gray-500">Total Leads</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $leadCounts['new'] }}</div>
            <div class="text-sm text-gray-500">New</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $leadCounts['contacted'] }}</div>
            <div class="text-sm text-gray-500">Contacted</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $leadCounts['hot'] }}</div>
            <div class="text-sm text-gray-500">Hot Leads</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $leadCounts['warm'] }}</div>
            <div class="text-sm text-gray-500">Warm Leads</div>
        </div>
    </div>

    <!-- Broadcast Form -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Compose Message</h3>
        </div>
        <form action="{{ route('admin.lead-automation.broadcast') }}" method="POST" class="p-6">
            @csrf
            
            <div class="mb-6">
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                <textarea id="message" name="message" rows="6" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your broadcast message here..." required></textarea>
                <div class="mt-2 text-sm text-gray-500">
                    {{ 1000 - (old('message') ? strlen(old('message')) : 0) }} characters remaining
                </div>
            </div>

            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Send To (Optional Filters)</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                        <select name="filters[status]" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            @foreach(\App\Helpers\PlatformHelper::getLeadStatuses() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Platform</label>
                        <select name="filters[platform]" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Platforms</option>
                            @foreach(\App\Helpers\PlatformHelper::getLeadCapturePlatforms() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Qualification</label>
                        <select name="filters[qualification]" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Qualifications</option>
                            @foreach(\App\Helpers\PlatformHelper::getLeadQualifications() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Message Templates -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Quick Templates</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button type="button" onclick="useTemplate('Hello! Just wanted to follow up and see if you have any questions. How can I help you today?')" class="text-left p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                        <div class="font-medium text-sm">General Follow-up</div>
                        <div class="text-xs text-gray-500 mt-1">Friendly check-in message</div>
                    </button>
                    <button type="button" onclick="useTemplate('Hi! We have a special offer for you. Would you be interested in learning more?')" class="text-left p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                        <div class="font-medium text-sm">Special Offer</div>
                        <div class="text-xs text-gray-500 mt-1">Promotional message</div>
                    </button>
                    <button type="button" onclick="useTemplate('Hello! Hope you\'re doing well. Just wanted to touch base and see if there\'s anything you need.')" class="text-left p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                        <div class="font-medium text-sm">Relationship Building</div>
                        <div class="text-xs text-gray-500 mt-1">Keep in touch message</div>
                    </button>
                    <button type="button" onclick="useTemplate('Hi! I wanted to personally reach out and thank you for your interest. Is there a good time to chat?')" class="text-left p-3 border border-gray-200 rounded-md hover:bg-gray-50">
                        <div class="font-medium text-sm">Personal Touch</div>
                        <div class="text-xs text-gray-500 mt-1">Direct outreach</div>
                    </button>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.lead-automation.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
                    📢 Send Broadcast
                </button>
            </div>
        </form>
    </div>

    <!-- Important Notice -->
    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>• Only leads with phone numbers will receive this message</p>
                    <p>• Please respect WhatsApp's anti-spam policies</p>
                    <p>• Consider time zones when scheduling broadcasts</p>
                    <p>• Personalize messages when possible for better engagement</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function useTemplate(message) {
    document.getElementById('message').value = message;
    updateCharCount();
}

function updateCharCount() {
    const message = document.getElementById('message').value;
    const remaining = 1000 - message.length;
    const counter = document.querySelector('.text-sm.text-gray-500');
    counter.textContent = remaining + ' characters remaining';
    
    if (remaining < 100) {
        counter.classList.add('text-red-600');
        counter.classList.remove('text-gray-500');
    } else {
        counter.classList.remove('text-red-600');
        counter.classList.add('text-gray-500');
    }
}

document.getElementById('message').addEventListener('input', updateCharCount);
</script>
@endsection
