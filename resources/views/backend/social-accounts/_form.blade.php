<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
        <select id="platform" name="platform" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select a platform</option>
            @foreach(\App\Helpers\PlatformHelper::getPlatforms() as $key => $label)
                <option value="{{ $key }}" {{ old('platform', $socialAccount->platform ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="account_name" class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
        <input type="text" id="account_name" name="account_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., My Business Account">
        <p class="mt-1 text-sm text-gray-500">A friendly name to identify this account</p>
    </div>

    <div>
        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">Account ID</label>
        <input type="text" id="account_id" name="account_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Platform account ID">
        <p class="mt-1 text-sm text-gray-500">The unique identifier from the platform</p>
    </div>

    <div>
        <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
        <input type="password" id="access_token" name="access_token" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Access token">
        <p class="mt-1 text-sm text-gray-500">Secure access token for API integration</p>
    </div>

    <div>
        <label for="refresh_token" class="block text-sm font-medium text-gray-700 mb-1">Refresh Token (Optional)</label>
        <input type="password" id="refresh_token" name="refresh_token" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Refresh token">
        <p class="mt-1 text-sm text-gray-500">Token to renew access when it expires</p>
    </div>

    <div class="md:col-span-2 flex items-start">
        <div class="flex items-center h-5">
            <input id="is_active" name="is_active" type="checkbox" checked class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
        </div>
        <div class="ml-3 text-sm">
            <label for="is_active" class="font-medium text-gray-700">Active</label>
            <p class="text-gray-500">Enable this account for automation</p>
        </div>
    </div>
</div>