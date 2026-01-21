<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Rule Name</label>
        <input type="text" name="name" id="name" value="{{ isset($automationRule) ? $automationRule->name : old('name') }}" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="trigger_conditions" class="block text-sm font-medium text-gray-700">Trigger Conditions</label>
        <textarea id="trigger_conditions" name="trigger_conditions" rows="3" placeholder='{"keywords": ["hello", "hi"], "user_types": ["follower"]}' class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ isset($automationRule) ? $automationRule->trigger_conditions : old('trigger_conditions') }}</textarea>
        <p class="mt-1 text-sm text-gray-500">JSON conditions for triggering the rule (e.g., keywords, user types)</p>
        @error('trigger_conditions')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="trigger_type" class="block text-sm font-medium text-gray-700">Trigger Type</label>
        <select id="trigger_type" name="trigger_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">Select Trigger Type</option>
            <option value="new_comment" {{ (isset($automationRule) && $automationRule->trigger_type == 'new_comment') || old('trigger_type') == 'new_comment' ? 'selected' : '' }}>New Comment</option>
            <option value="new_message" {{ (isset($automationRule) && $automationRule->trigger_type == 'new_message') || old('trigger_type') == 'new_message' ? 'selected' : '' }}>New Message</option>
            <option value="new_follower" {{ (isset($automationRule) && $automationRule->trigger_type == 'new_follower') || old('trigger_type') == 'new_follower' ? 'selected' : '' }}>New Follower</option>
            <option value="mention" {{ (isset($automationRule) && $automationRule->trigger_type == 'mention') || old('trigger_type') == 'mention' ? 'selected' : '' }}>Mention</option>
            <option value="like" {{ (isset($automationRule) && $automationRule->trigger_type == 'like') || old('trigger_type') == 'like' ? 'selected' : '' }}>Like</option>
        </select>
        @error('trigger_type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="action_type" class="block text-sm font-medium text-gray-700">Action Type</label>
        <select id="action_type" name="action_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">Select Action Type</option>
            <option value="auto_reply" {{ (isset($automationRule) && $automationRule->action_type == 'auto_reply') || old('action_type') == 'auto_reply' ? 'selected' : '' }}>Auto Reply</option>
            <option value="send_dm" {{ (isset($automationRule) && $automationRule->action_type == 'send_dm') || old('action_type') == 'send_dm' ? 'selected' : '' }}>Send Direct Message</option>
            <option value="tag_lead" {{ (isset($automationRule) && $automationRule->action_type == 'tag_lead') || old('action_type') == 'tag_lead' ? 'selected' : '' }}>Tag as Lead</option>
            <option value="send_notification" {{ (isset($automationRule) && $automationRule->action_type == 'send_notification') || old('action_type') == 'send_notification' ? 'selected' : '' }}>Send Notification</option>
        </select>
        @error('action_type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="action_parameters" class="block text-sm font-medium text-gray-700">Action Parameters</label>
        <textarea id="action_parameters" name="action_parameters" rows="4" placeholder='{"response_template": "Thank you for your message!", "delay_seconds": 30}' class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ isset($automationRule) ? $automationRule->action_parameters : old('action_parameters') }}</textarea>
        <p class="mt-1 text-sm text-gray-500">JSON parameters for the action (e.g., response templates, delays)</p>
        @error('action_parameters')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="is_active" class="block text-sm font-medium text-gray-700">Status</label>
        <select id="is_active" name="is_active" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="1" {{ (isset($automationRule) && $automationRule->is_active) || old('is_active', true) ? 'selected' : '' }}>Active</option>
            <option value="0" {{ (isset($automationRule) && !$automationRule->is_active) || old('is_active') === false ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('is_active')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>