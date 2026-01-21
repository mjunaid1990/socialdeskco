@extends('backend.layouts.app')

@section('title', 'Lead Details - ' . $lead->first_name)

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $lead->first_name }} {{ $lead->last_name }}</h1>
                <p class="mt-1 text-sm text-gray-600">Lead details and automation options</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.lead-automation.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Back to Leads
                </a>
                <a href="{{ route('admin.leads.edit', $lead->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                    Edit Lead
                </a>
            </div>
        </div>
    </div>

    <!-- Lead Score and Qualification -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Score</h3>
            <div class="flex items-center justify-center">
                <div class="relative">
                    <svg class="w-32 h-32 transform -rotate-90">
                        <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none"></circle>
                        <circle cx="64" cy="64" r="56" stroke="#3b82f6" stroke-width="12" fill="none"
                            stroke-dasharray="{{ 351.86 * ($score / 100) }} 351.86"
                            stroke-linecap="round"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-900">{{ $score }}/100</span>
                    </div>
                </div>
            </div>
            <form action="{{ route('admin.lead-automation.rescore', $lead->id) }}" method="POST" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    🔄 Rescore Lead
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Qualification</h3>
            <div class="text-center">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium
                    @if($qualification === 'hot') bg-red-100 text-red-800
                    @elseif($qualification === 'warm') bg-yellow-100 text-yellow-800
                    @else bg-blue-100 text-blue-800
                    @endif">
                    {{ ucfirst($qualification) }} Lead
                </span>
            </div>
            <form action="{{ route('admin.lead-automation.update-qualification', $lead->id) }}" method="POST" class="mt-4">
                @csrf
                @method('PUT')
                <select name="qualification" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(\App\Helpers\PlatformHelper::getLeadQualifications() as $key => $label)
                        <option value="{{ $key }}" {{ $lead->qualification === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm">
                    Update Qualification
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                @if($lead->phone)
                    <button onclick="openWhatsAppModal()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                        📱 Send WhatsApp Message
                    </button>
                @else
                    <button disabled class="w-full bg-gray-300 text-gray-500 px-4 py-2 rounded-md text-sm cursor-not-allowed">
                        📱 No Phone Number
                    </button>
                @endif
                
                <button onclick="openEmailModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                    ✉️ Send Email
                </button>
                
                <button onclick="updateLeadStatus('converted')" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm">
                    ✅ Mark as Converted
                </button>
            </div>
        </div>
    </div>

    <!-- Lead Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Lead Information</h3>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
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
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ \App\Helpers\PlatformHelper::getPlatformLabel($lead->platform) }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Source</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ \App\Helpers\PlatformHelper::getSourceTypeLabel($lead->source_type) }}</dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($lead->status === 'new') bg-green-100 text-green-800
                                @elseif($lead->status === 'contacted') bg-blue-100 text-blue-800
                                @elseif($lead->status === 'converted') bg-purple-100 text-purple-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($lead->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Follow-ups</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->followup_count ?? 0 }} sent</dd>
                    </div>
                    @if($lead->last_followup_at)
                    <div class="sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Last Follow-up</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $lead->last_followup_at->format('M d, Y h:i A') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Original Message</h3>
            </div>
            <div class="p-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $lead->message }}</p>
                </div>
                <div class="mt-4 text-xs text-gray-500">
                    Received: {{ $lead->created_at->format('M d, Y h:i A') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Automation History -->
    @if($lead->automation_history)
    <div class="mt-6 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Automation History</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($lead->automation_history as $event)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            @if($event['type'] === 'custom_whatsapp')
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-xs font-medium text-green-600">📱</span>
                                </div>
                            @else
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-xs font-medium text-blue-600">🤖</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $event['type'])) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $event['message'] ?? 'Automated action' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ \Carbon\Carbon::parse($event['sent_at'])->format('M d, Y h:i A') }}
                                @if(isset($event['sent_by']))
                                    • by {{ $event['sent_by'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- WhatsApp Message Modal -->
<div id="whatsappModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Send WhatsApp Message</h3>
            <form action="{{ route('admin.lead-automation.send-whatsapp', $lead->id) }}" method="POST" class="mt-4">
                @csrf
                <textarea name="message" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message here..." required></textarea>
                <div class="mt-4 flex justify-end space-x-3">
                    <button type="button" onclick="closeWhatsAppModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openWhatsAppModal() {
    document.getElementById('whatsappModal').classList.remove('hidden');
}

function closeWhatsAppModal() {
    document.getElementById('whatsappModal').classList.add('hidden');
}

function openEmailModal() {
    alert('Email functionality coming soon!');
}

function updateLeadStatus(status) {
    if (confirm('Are you sure you want to mark this lead as ' + status + '?')) {
        // You can implement this functionality
        alert('Status update functionality coming soon!');
    }
}
</script>
@endsection
