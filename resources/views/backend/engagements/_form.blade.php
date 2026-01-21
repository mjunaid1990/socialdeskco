<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="platform" class="block text-sm font-medium text-gray-700">Platform</label>
        <select id="platform" name="platform" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">Select Platform</option>
            @foreach(\App\Helpers\PlatformHelper::getPlatforms() as $key => $label)
                <option value="{{ $key }}" {{ (isset($engagement) && $engagement->platform == $key) || old('platform') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('platform')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="engagement_type" class="block text-sm font-medium text-gray-700">Engagement Type</label>
        <select id="engagement_type" name="engagement_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">Select Type</option>
            <option value="like" {{ (isset($engagement) && $engagement->engagement_type == 'like') || old('engagement_type') == 'like' ? 'selected' : '' }}>Like</option>
            <option value="comment" {{ (isset($engagement) && $engagement->engagement_type == 'comment') || old('engagement_type') == 'comment' ? 'selected' : '' }}>Comment</option>
            <option value="share" {{ (isset($engagement) && $engagement->engagement_type == 'share') || old('engagement_type') == 'share' ? 'selected' : '' }}>Share</option>
            <option value="follow" {{ (isset($engagement) && $engagement->engagement_type == 'follow') || old('engagement_type') == 'follow' ? 'selected' : '' }}>Follow</option>
            <option value="message" {{ (isset($engagement) && $engagement->engagement_type == 'message') || old('engagement_type') == 'message' ? 'selected' : '' }}>Message</option>
        </select>
        @error('engagement_type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
        <textarea id="content" name="content" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ isset($engagement) ? $engagement->content : old('content') }}</textarea>
        @error('content')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="engagement_score" class="block text-sm font-medium text-gray-700">Engagement Score</label>
        <input type="number" name="engagement_score" id="engagement_score" value="{{ isset($engagement) ? $engagement->engagement_score : old('engagement_score', 0) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        @error('engagement_score')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="is_automated_response" class="block text-sm font-medium text-gray-700">Automated Response?</label>
        <select id="is_automated_response" name="is_automated_response" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="0" {{ (isset($engagement) && !$engagement->is_automated_response) || old('is_automated_response') == '0' ? 'selected' : '' }}>No</option>
            <option value="1" {{ (isset($engagement) && $engagement->is_automated_response) || old('is_automated_response') == '1' ? 'selected' : '' }}>Yes</option>
        </select>
        @error('is_automated_response')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>