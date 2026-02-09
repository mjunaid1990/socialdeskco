<div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-x-4"
     x-transition:enter-end="opacity-100 transform translate-x-0">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform-Specific Fields</h3>
        <p class="text-sm text-gray-600 mb-4">Additional fields required by your selected platforms</p>
        
        <!-- Dynamic platform-specific fields -->
        <div class="space-y-6">
            <!-- Facebook Specific Fields -->
            <div x-show="selectedAccounts.some(account => getAccountPlatform(account) === 'facebook')" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook Options
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Privacy Setting</label>
                        <select name="facebook_settings[privacy]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="public" {{ (old('facebook_settings.privacy', $post->facebook_settings['privacy'] ?? '') == 'public') ? 'selected' : '' }}>Public</option>
                            <option value="friends" {{ (old('facebook_settings.privacy', $post->facebook_settings['privacy'] ?? '') == 'friends') ? 'selected' : '' }}>Friends</option>
                            <option value="only_me" {{ (old('facebook_settings.privacy', $post->facebook_settings['privacy'] ?? '') == 'only_me') ? 'selected' : '' }}>Only Me</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Feeling/Activity</label>
                        <input type="text" name="facebook_settings[feeling]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Happy, Excited" value="{{ old('facebook_settings.feeling', $post->facebook_settings['feeling'] ?? '') }}">
                    </div>
                </div>
            </div>

            <!-- Instagram Specific Fields -->
            <div x-show="selectedAccounts.some(account => getAccountPlatform(account) === 'instagram')" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-pink-600 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.281-.073-1.689-.073-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z"/></svg>
                    Instagram Options
                </h4>
                <div class="space-y-4 ml-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alt Text (Accessibility)</label>
                        <textarea name="instagram_settings[alt_text]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Describe your image for visually impaired users">{{ old('instagram_settings.alt_text', $post->instagram_settings['alt_text'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location Tag</label>
                        <input type="text" name="instagram_settings[location]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add a location tag" value="{{ old('instagram_settings.location', $post->instagram_settings['location'] ?? '') }}">
                    </div>
                </div>
            </div>

            <!-- X/Twitter Specific Fields -->
            <div x-show="selectedAccounts.some(account => getAccountPlatform(account) === 'x')" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-black mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    X (Twitter) Options
                </h4>
                <div class="space-y-4 ml-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thread Connection</label>
                        <select name="x_settings[thread_connection]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="" {{ (old('x_settings.thread_connection', $post->x_settings['thread_connection'] ?? '') == '') ? 'selected' : '' }}>Start new thread</option>
                            <option value="reply" {{ (old('x_settings.thread_connection', $post->x_settings['thread_connection'] ?? '') == 'reply') ? 'selected' : '' }}>Reply to previous tweet</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- LinkedIn Specific Fields -->
            <div x-show="selectedAccounts.some(account => getAccountPlatform(account) === 'linkedin')" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-blue-700 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    LinkedIn Options
                </h4>
                <div class="space-y-4 ml-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Professional Hashtags</label>
                        <input type="text" name="linkedin_settings[hashtags]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="#Professional #Networking #Career" value="{{ old('linkedin_settings.hashtags', $post->linkedin_settings['hashtags'] ?? '') }}">
                    </div>
                </div>
            </div>

            <!-- YouTube Specific Fields -->
            <div x-show="selectedAccounts.some(account => getAccountPlatform(account) === 'youtube')" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    YouTube Options
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="youtube_settings[category]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '') ? 'selected' : '' }}>Select category...</option>
                            <option value="1" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '1') ? 'selected' : '' }}>Film & Animation</option>
                            <option value="2" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '2') ? 'selected' : '' }}>Autos & Vehicles</option>
                            <option value="10" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '10') ? 'selected' : '' }}>Music</option>
                            <option value="15" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '15') ? 'selected' : '' }}>Pets & Animals</option>
                            <option value="17" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '17') ? 'selected' : '' }}>Sports</option>
                            <option value="19" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '19') ? 'selected' : '' }}>Travel & Events</option>
                            <option value="20" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '20') ? 'selected' : '' }}>Gaming</option>
                            <option value="22" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '22') ? 'selected' : '' }}>People & Blogs</option>
                            <option value="23" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '23') ? 'selected' : '' }}>Comedy</option>
                            <option value="24" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '24') ? 'selected' : '' }}>Entertainment</option>
                            <option value="25" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '25') ? 'selected' : '' }}>News & Politics</option>
                            <option value="26" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '26') ? 'selected' : '' }}>Howto & Style</option>
                            <option value="27" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '27') ? 'selected' : '' }}>Education</option>
                            <option value="28" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '28') ? 'selected' : '' }}>Science & Technology</option>
                            <option value="29" {{ (old('youtube_settings.category', $post->youtube_settings['category'] ?? '') == '29') ? 'selected' : '' }}>Nonprofits & Activism</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Privacy Status <span class="text-red-500">*</span></label>
                        <select name="youtube_settings[privacy]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="public" {{ (old('youtube_settings.privacy', $post->youtube_settings['privacy'] ?? 'public') == 'public') ? 'selected' : '' }}>Public</option>
                            <option value="unlisted" {{ (old('youtube_settings.privacy', $post->youtube_settings['privacy'] ?? '') == 'unlisted') ? 'selected' : '' }}>Unlisted</option>
                            <option value="private" {{ (old('youtube_settings.privacy', $post->youtube_settings['privacy'] ?? '') == 'private') ? 'selected' : '' }}>Private</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <input type="text" name="youtube_settings[tags]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Separate tags with commas" value="{{ old('youtube_settings.tags', $post->youtube_settings['tags'] ?? '') }}">
                        <p class="mt-1 text-sm text-gray-500">Maximum 500 characters total</p>
                    </div>
                </div>
            </div>

            <!-- TikTok Specific Fields -->
            <div x-show="selectedAccounts.some(account => getAccountPlatform(account) === 'tiktok')" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 text-black mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                    TikTok Options
                </h4>
                <div class="space-y-4 ml-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Caption <span class="text-red-500">*</span></label>
                        <textarea name="tiktok_settings[caption]" required rows="3" maxlength="2200" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Write your caption with #hashtags and @mentions">{{ old('tiktok_settings.caption', $post->tiktok_settings['caption'] ?? '') }}</textarea>
                        <div class="mt-1 text-sm text-gray-500 flex justify-between">
                            <span>Add #hashtags and @mentions for better discovery</span>
                            <span id="tiktok-caption-count">0</span> / 2200
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Privacy Setting</label>
                        <select name="tiktok_settings[privacy]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="PUBLIC_TO_EVERYONE" {{ (old('tiktok_settings.privacy', $post->tiktok_settings['privacy'] ?? 'PUBLIC_TO_EVERYONE') == 'PUBLIC_TO_EVERYONE') ? 'selected' : '' }}>Public</option>
                            <option value="FRIENDS" {{ (old('tiktok_settings.privacy', $post->tiktok_settings['privacy'] ?? '') == 'FRIENDS') ? 'selected' : '' }}>Friends</option>
                            <option value="SELF_ONLY" {{ (old('tiktok_settings.privacy', $post->tiktok_settings['privacy'] ?? '') == 'SELF_ONLY') ? 'selected' : '' }}>Only Me</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="tiktok_settings[disable_duet]" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ old('tiktok_settings.disable_duet', $post->tiktok_settings['disable_duet'] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Disable Duet</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="tiktok_settings[disable_stitch]" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ old('tiktok_settings.disable_stitch', $post->tiktok_settings['disable_stitch'] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Disable Stitch</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="tiktok_settings[disable_comments]" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ old('tiktok_settings.disable_comments', $post->tiktok_settings['disable_comments'] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Disable Comments</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Post Settings Section -->
        <div class="mt-8 pt-6 border-t border-gray-300">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Post Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="post_status" class="block text-sm font-medium text-gray-700 mb-2">
                        Post Status <span class="text-red-500">*</span>
                    </label>
                    <select id="post_status" name="status" 
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        <option value="draft" {{ (old('status', 'draft') == 'draft') ? 'selected' : '' }}>📝 Draft</option>
                        <option value="scheduled" {{ (old('status', 'draft') == 'scheduled') ? 'selected' : '' }}>⏰ Scheduled</option>
                        <option value="published" {{ (old('status', 'draft') == 'published') ? 'selected' : '' }}>✅ Published</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Choose when you want this post to go live</p>
                </div>
                
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">
                        Schedule Date & Time
                    </label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           value="{{ old('scheduled_at') }}">
                    <p class="mt-1 text-sm text-gray-500">Leave empty for immediate publishing when status is "Published"</p>
                </div>
            </div>
        </div>
    </div>
</div>