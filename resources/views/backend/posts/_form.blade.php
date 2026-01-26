<div class="space-y-8">
    <!-- Basic Information Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 rounded-full p-2 mr-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                <p class="text-sm text-gray-500">Enter the basic details for your post</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Post Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" 
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                       placeholder="Enter a catchy title for your post" 
                       value="{{ old('title', $post->title ?? '') }}">
                <p class="mt-1 text-sm text-gray-500">A brief, descriptive title for your social media post</p>
            </div>

            <div>
                <label for="social_accounts" class="block text-sm font-medium text-gray-700 mb-2">
                    Social Accounts <span class="text-red-500">*</span>
                </label>
                <select id="social_accounts" name="social_accounts[]" multiple 
                        class="form-control"
                        placeholder="Select accounts to post to...">
                    @foreach(\App\Models\SocialAccount::where('user_id', Auth::id())->where('is_active', true)->get() as $account)
                        <option value="{{ $account->id }}" 
                                data-platform="{{ $account->platform }}"
                                data-custom-properties='{"platform": "{{ $account->platform }}"}'
                                {{ (old('social_accounts', $post->social_accounts ?? []) && in_array($account->id, old('social_accounts', $post->social_accounts ?? []))) ? 'selected' : '' }}>
                            {{ $account->account_name }} ({{ ucfirst($account->platform) }})
                        </option>
                    @endforeach
                </select>
                
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Post Status <span class="text-red-500">*</span>
                </label>
                <select id="status" name="status" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                    <option value="draft" {{ (old('status', $post->status ?? 'draft') == 'draft') ? 'selected' : '' }}>📝 Draft</option>
                    <option value="scheduled" {{ (old('status', $post->status ?? 'draft') == 'scheduled') ? 'selected' : '' }}>⏰ Scheduled</option>
                    <option value="published" {{ (old('status', $post->status ?? 'draft') == 'published') ? 'selected' : '' }}>✅ Published</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Choose when you want this post to go live</p>
            </div>
            
            <div>
                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">
                    Schedule Date & Time
                </label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" 
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                       value="{{ old('scheduled_at', $post->scheduled_at ? $post->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                <p class="mt-1 text-sm text-gray-500">When to publish this post (for scheduled posts)</p>
            </div>
        </div>
    </div>

    <!-- AI Assistant Section -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="bg-blue-600 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">AI Content Assistant</h3>
                    <p class="text-sm text-gray-600">Let AI help you create engaging content and images</p>
                </div>
            </div>
            <button type="button" id="toggle-ai-panel" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Generate with AI
            </button>
        </div>
        
        <div id="ai-generation-panel" class="hidden mt-6">
            <!-- Tab navigation -->
            <div class="bg-white rounded-lg border border-gray-200 p-1 mb-6">
                <nav class="flex space-x-1" aria-label="Tabs">
                    <button type="button" id="content-tab" class="tab-button flex-1 flex items-center justify-center px-4 py-3 text-sm font-medium rounded-md bg-blue-600 text-white transition duration-200" data-target="content-section">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Content
                    </button>
                    <button type="button" id="image-tab" class="tab-button flex-1 flex items-center justify-center px-4 py-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition duration-200" data-target="image-section">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Image
                    </button>
                    <button type="button" id="combined-tab" class="tab-button flex-1 flex items-center justify-center px-4 py-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition duration-200" data-target="combined-section">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Combined
                    </button>
                </nav>
            </div>
            
            <!-- Content Generation Section -->
            <div id="content-section" class="tab-content bg-white rounded-lg p-6 border border-gray-200">
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Generate Content</h4>
                    <p class="text-sm text-gray-600">Create engaging social media posts with AI assistance</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai-topic" class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                        <input type="text" id="ai-topic" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter topic for AI generation">
                    </div>
                    <div>
                        <label for="ai-platform" class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                        <select id="ai-platform" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Platform</option>
                            @foreach(\App\Helpers\PlatformHelper::getPostingPlatforms() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai-tone" class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                        <select id="ai-tone" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Default</option>
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="funny">Funny</option>
                            <option value="inspirational">Inspirational</option>
                            <option value="informative">Informative</option>
                        </select>
                    </div>
                    <div>
                        <label for="ai-max-length" class="block text-sm font-medium text-gray-700 mb-1">Max Length (chars)</label>
                        <input type="number" id="ai-max-length" min="50" max="5000" value="280" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Max character length">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="ai-keywords" class="block text-sm font-medium text-gray-700 mb-2">Keywords (comma separated)</label>
                    <input type="text" id="ai-keywords" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" placeholder="keyword1, keyword2, keyword3">
                    <p class="mt-1 text-sm text-gray-500">Add keywords to improve content relevance</p>
                </div>
                
                <div class="flex flex-row gap-3">
                    <button type="button" id="generate-single" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Generate Single Post
                    </button>
                    <button type="button" id="generate-variations" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Generate Variations
                    </button>
                    <button type="button" id="generate-and-fill" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Generate & Fill
                    </button>
                </div>
            </div>
            
            <!-- Image Generation Section -->
            <div id="image-section" class="tab-content hidden bg-white rounded-lg p-6 border border-gray-200">
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Generate Image</h4>
                    <p class="text-sm text-gray-600">Create stunning images with AI assistance</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai-image-prompt" class="block text-sm font-medium text-gray-700 mb-1">Image Prompt</label>
                        <input type="text" id="ai-image-prompt" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Describe the image you want to generate">
                    </div>
                    <div>
                        <label for="ai-image-size" class="block text-sm font-medium text-gray-700 mb-1">Image Size</label>
                        <select id="ai-image-size" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1024x1024">1024x1024 (Standard)</option>
                            <option value="256x256">256x256 (Small)</option>
                            <option value="512x512">512x512 (Medium)</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai-image-style" class="block text-sm font-medium text-gray-700 mb-1">Style</label>
                        <select id="ai-image-style" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="vivid">Vivid</option>
                            <option value="natural">Natural</option>
                        </select>
                    </div>
                    <div>
                        <label for="ai-image-quality" class="block text-sm font-medium text-gray-700 mb-1">Quality</label>
                        <select id="ai-image-quality" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="standard">Standard</option>
                            <option value="hd">HD</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="ai-image-topic" class="block text-sm font-medium text-gray-700 mb-2">Related Topic (optional)</label>
                    <input type="text" id="ai-image-topic" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" placeholder="Topic related to the image">
                    <p class="mt-1 text-sm text-gray-500">Help AI generate more relevant images</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" id="generate-image" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Generate Image
                    </button>
                    <button type="button" id="generate-image-from-content" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Generate from Content
                    </button>
                </div>
            </div>
            
            <!-- Combined Generation Section -->
            <div id="combined-section" class="tab-content hidden bg-white rounded-lg p-6 border border-gray-200">
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Generate Content & Image</h4>
                    <p class="text-sm text-gray-600">Create complete posts with both content and images</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai-combined-topic" class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                        <input type="text" id="ai-combined-topic" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter topic for AI generation">
                    </div>
                    <div>
                        <label for="platforms" class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                        <select id="platforms" name="platforms[]" multiple class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @foreach(\App\Helpers\PlatformHelper::getPostingPlatforms() as $key => $label)
                                <option value="{{ $key }}" {{ (isset($post) && in_array($key, $post->platforms ?? [])) || (old('platforms') && in_array($key, old('platforms'))) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="ai-combined-tone" class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                        <select id="ai-combined-tone" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Default</option>
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="funny">Funny</option>
                            <option value="inspirational">Inspirational</option>
                            <option value="informative">Informative</option>
                        </select>
                    </div>
                    <div>
                        <label for="ai-combined-size" class="block text-sm font-medium text-gray-700 mb-1">Image Size</label>
                        <select id="ai-combined-size" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1024x1024">1024x1024 (Standard)</option>
                            <option value="256x256">256x256 (Small)</option>
                            <option value="512x512">512x512 (Medium)</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" id="generate-combined" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Generate Content & Image
                    </button>
                </div>
            </div>
            
            <div class="flex space-x-3 mb-4 mt-4">
                <button type="button" id="close-ai-panel" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition duration-300">
                    Close
                </button>
            </div>
            
            <div id="ai-loading" class="hidden mb-4">
                <div class="flex items-center text-blue-600">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing with AI...
                </div>
            </div>
            
            <div id="ai-results" class="hidden mb-4">
                <h4 class="font-medium text-gray-900 mb-2">AI Results:</h4>
                <div id="generated-content-result" class="bg-white p-3 rounded border border-gray-300 mb-3 min-h-[100px] hidden"></div>
                <div id="generated-image-result" class="mb-3 hidden">
                    <h5 class="font-medium text-gray-900 mb-2">Generated Image:</h5>
                    <img id="generated-image" src="" alt="Generated Image" class="max-w-full h-auto rounded border border-gray-300" />
                    <button type="button" id="apply-image" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-300">
                        Apply Image to Post
                    </button>
                </div>
                <div id="variations-container" class="hidden">
                    <h5 class="font-medium text-gray-900 mb-2">Select a variation:</h5>
                    <div id="variations-list" class="space-y-2"></div>
                </div>
                <div class="flex space-x-3 mt-3">
                    <button type="button" id="apply-content" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-300">
                        Apply Content to Post
                    </button>
                    <button type="button" id="regenerate-content" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition duration-300">
                        Regenerate
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="bg-green-100 rounded-full p-2 mr-3">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Post Content</h3>
                <p class="text-sm text-gray-500">Write your message or use AI to generate content</p>
            </div>
        </div>
        
        <div>
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                Message Content <span class="text-red-500">*</span>
            </label>
            <textarea id="content" name="content" rows="6" 
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none" 
                      placeholder="Share your thoughts, updates, or promotions with your audience...">{{ old('content', $post->content ?? '') }}</textarea>
            <div class="flex justify-between items-center mt-2">
                <p class="text-sm text-gray-500">Write your post content or use AI generation below</p>
                <span id="char-count" class="text-sm text-gray-400">0 characters</span>
            </div>
        </div>
    </div>
    
    <!-- Media Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
            <div class="bg-purple-100 rounded-full p-2 mr-3">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Media Files</h3>
                <p class="text-sm text-gray-500">Add images, videos, or other media to your post</p>
            </div>
        </div>
        
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition duration-200">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4M28 28l4-4M5 32v4a2 2 0 002 2h4M7 16a4 4 0 018 0v0a4 4 0 01-8 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
                <label for="media" class="cursor-pointer">
                    <span class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 inline-block">
                        Choose Files
                    </span>
                    <input id="media" name="media" type="file" class="sr-only" multiple>
                </label>
                <p class="mt-2 text-sm text-gray-600">or drag and drop files here</p>
                <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB each</p>
            </div>
        </div>
        
        <!-- Existing Media Files -->
        @if(isset($post) && $post->media_files && !empty($post->media_files))
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Current Media Files</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="existing-media-files">
                @foreach($post->media_files as $index => $mediaFile)
                <div class="relative group existing-media-item" data-index="{{ $index }}">
                    <div class="aspect-square rounded-lg overflow-hidden border border-gray-200">
                        @if(str_contains($mediaFile, '.jpg') || str_contains($mediaFile, '.jpeg') || str_contains($mediaFile, '.png') || str_contains($mediaFile, '.gif'))
                            <img src="{{ $mediaFile }}" alt="Media file {{ $index + 1 }}" class="w-full h-full object-cover">
                        @elseif(str_contains($mediaFile, '.mp4') || str_contains($mediaFile, '.mov') || str_contains($mediaFile, '.avi'))
                            <video src="{{ $mediaFile }}" class="w-full h-full object-cover" controls></video>
                        @else
                            <div class="w-full h-full bg-gray-100 flex flex-col items-center justify-center p-4">
                                <div class="text-2xl mb-2">📎</div>
                                <p class="text-xs text-gray-600 text-center truncate">Media File</p>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                            <button type="button" onclick="removeExistingMedia({{ $index }})" class="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-600 truncate">File {{ $index + 1 }}</p>
                    <input type="hidden" name="existing_media_files[]" value="{{ $mediaFile }}" class="existing-media-input">
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing Choices.js...');
    
    // Function to initialize Choices.js
    function initializeChoices() {
        // Check if Choices is available (from npm)
        if (typeof window.Choices === 'undefined') {
            console.error('Choices.js is not loaded!');
            
            // Fallback: Convert to basic multi-select with styling
            initializeFallbackMultiSelect();
            return;
        }
        
        console.log('Choices.js (npm version) is available');
        
        // Initialize Choices for multi-select social accounts
        const socialAccountsSelect = document.getElementById('social_accounts');
        
        if (socialAccountsSelect) {
            try {
                console.log('Attempting to initialize Choices.js...');
                
                // Initialize Choices
                const choices = new Choices(socialAccountsSelect, {
                    removeItemButton: true,
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search accounts...',
                    placeholderValue: 'Select accounts to post to...',
                    loadingText: 'Loading...',
                    noResultsText: 'No accounts found',
                    noChoicesText: 'No choices to choose from',
                    itemSelectText: 'Press to select',
                    uniqueItemText: 'Only unique values can be added'
                });
                
                
            } catch (error) {
                console.error('Error initializing Choices.js:', error);
                initializeFallbackMultiSelect();
            }
        } else {
            console.error('Social accounts select element not found!');
        }
    }
    
    // Fallback multi-select functionality
    function initializeFallbackMultiSelect() {
        console.log('Initializing fallback multi-select...');
        
        const socialAccountsSelect = document.getElementById('social_accounts');
        const selectedAccountsPreview = document.getElementById('selected-accounts-preview');
        const selectedAccountsList = document.getElementById('selected-accounts-list');
        
        if (socialAccountsSelect) {
            // Make it multiple and add styling
            socialAccountsSelect.setAttribute('multiple', 'multiple');
            socialAccountsSelect.style.height = '120px';
            socialAccountsSelect.style.border = '2px solid #e5e7eb';
            socialAccountsSelect.style.borderRadius = '8px';
            socialAccountsSelect.style.padding = '8px';
            
            // Add change listener for preview
            socialAccountsSelect.addEventListener('change', function() {
                const selectedOptions = Array.from(this.selectedOptions);
                
                if (selectedOptions.length > 0) {
                    selectedAccountsPreview.classList.remove('hidden');
                    selectedAccountsList.innerHTML = '';
                    
                    selectedOptions.forEach(option => {
                        const platform = option.dataset.platform || 'unknown';
                        const name = option.dataset.name || option.text;
                        const platformColors = {
                            'facebook': 'bg-blue-100 text-blue-800 border-blue-200',
                            'twitter': 'bg-sky-100 text-sky-800 border-sky-200',
                            'linkedin': 'bg-blue-100 text-blue-800 border-blue-200',
                            'instagram': 'bg-pink-100 text-pink-800 border-pink-200',
                            'youtube': 'bg-red-100 text-red-800 border-red-200',
                            'tiktok': 'bg-black text-white border-black',
                            'pinterest': 'bg-red-100 text-red-800 border-red-200',
                            'reddit': 'bg-orange-100 text-orange-800 border-orange-200'
                        };
                        const colorClass = platformColors[platform] || 'bg-gray-100 text-gray-800 border-gray-200';
                        
                        const badge = document.createElement('div');
                        badge.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border ${colorClass}`;
                        badge.innerHTML = `
                            <span class="mr-1">${name}</span>
                            <span class="opacity-75">(${platform.toUpperCase()})</span>
                        `;
                        selectedAccountsList.appendChild(badge);
                    });
                } else {
                    selectedAccountsPreview.classList.add('hidden');
                }
            });
            
            // Initial preview update
            socialAccountsSelect.dispatchEvent(new Event('change'));
            
            console.log('Fallback multi-select initialized successfully');
        }
    }
    
    // Try to initialize immediately
    initializeChoices();
    
    // If Choices is not loaded, wait a bit and try again
    if (typeof window.Choices === 'undefined') {
        console.log('Choices.js not loaded yet, waiting...');
        setTimeout(initializeChoices, 1000);
        setTimeout(initializeChoices, 2000);
        setTimeout(initializeChoices, 3000);
    }
    
    // Character counter for content
    const contentTextarea = document.getElementById('content');
    const charCount = document.getElementById('char-count');
    
    if (contentTextarea && charCount) {
        contentTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length + ' characters';
        });
        // Initialize count
        charCount.textContent = contentTextarea.value.length + ' characters';
    }
});
</script>