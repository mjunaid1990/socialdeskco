<div class="grid grid-cols-1 gap-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
            <input type="text" id="first_name" name="first_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter first name" value="{{ old('first_name', $lead->first_name ?? '') }}">
            @error('first_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter last name" value="{{ old('last_name', $lead->last_name ?? '') }}">
            @error('last_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" id="email" name="email" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter email address" value="{{ old('email', $lead->email ?? '') }}">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter phone number" value="{{ old('phone', $lead->phone ?? '') }}">
            @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="social_account_id" class="block text-sm font-medium text-gray-700 mb-1">Social Account *</label>
            <select id="social_account_id" name="social_account_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select a social account</option>
                @foreach($socialAccounts as $account)
                    <option value="{{ $account->id }}" {{ old('social_account_id', $lead->social_account_id ?? '') == $account->id ? 'selected' : '' }}>
                        {{ $account->account_name }} ({{ $account->platform }})
                    </option>
                @endforeach
            </select>
            @error('social_account_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <div>
            <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform *</label>
            <select id="platform" name="platform" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select platform</option>
                @foreach(\App\Helpers\PlatformHelper::getLeadCapturePlatforms() as $key => $label)
                    <option value="{{ $key }}" {{ old('platform', $lead->platform ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('platform')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="platform_lead_id" class="block text-sm font-medium text-gray-700 mb-1">Platform Lead ID *</label>
            <input type="text" id="platform_lead_id" name="platform_lead_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter platform lead ID" value="{{ old('platform_lead_id', $lead->platform_lead_id ?? '') }}">
            @error('platform_lead_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <div>
            <label for="source_type" class="block text-sm font-medium text-gray-700 mb-1">Source Type *</label>
            <select id="source_type" name="source_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select source type</option>
                @foreach(\App\Helpers\PlatformHelper::getSourceTypes() as $key => $label)
                    <option value="{{ $key }}" {{ old('source_type', $lead->source_type ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('source_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
        <textarea id="message" name="message" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter message">{{ old('message', $lead->message ?? '') }}</textarea>
        @error('message')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
        <select id="status" name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select status</option>
            @foreach(\App\Helpers\PlatformHelper::getLeadStatuses() as $key => $label)
                <option value="{{ $key }}" {{ old('status', $lead->status ?? 'new') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="custom_fields" class="block text-sm font-medium text-gray-700 mb-1">Custom Fields</label>
        <textarea id="custom_fields" name="custom_fields" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder='Enter custom fields as JSON'>{{ old('custom_fields', json_encode($lead->custom_fields ?? [])) }}</textarea>
        <p class="mt-1 text-sm text-gray-500">Enter custom fields as JSON format</p>
        @error('custom_fields')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>