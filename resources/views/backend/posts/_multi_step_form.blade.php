<!-- Multi-Step Form Container -->
<div class="multi-step-form">
    <!-- Progress Bar -->
    <div class="mb-8">
        <div class="flex items-center justify-between w-full">
            <template x-for="(step, index) in steps" :key="index">
                <div class="flex flex-col items-center flex-1 relative">
                    <!-- Step Circle -->
                    <div class="flex items-center justify-center w-10 h-10 rounded-full z-10 transition-colors duration-300"
                         :class="currentStep >= index ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'">
                        <span x-text="index + 1" class="font-medium"></span>
                    </div>
                    <!-- Step Title -->
                    <span class="mt-2 text-sm font-medium text-center transition-colors duration-300"
                          :class="currentStep >= index ? 'text-blue-600' : 'text-gray-500'"
                          x-text="step.title"></span>
                    <!-- Connector Line (except for last step) -->
                    <div x-show="index < steps.length - 1" 
                         class="absolute top-5 left-1/2 w-full h-0.5 bg-gray-300 -translate-y-1/2 z-0"
                         :class="currentStep > index ? 'bg-blue-600' : 'bg-gray-300'">
                    </div>
                </div>
            </template>
        </div>
        <div class="mt-6 bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                 :style="`width: ${(currentStep + 1) / steps.length * 100}%`"></div>
        </div>
    </div>

    <!-- Form Content -->
    <div>
        <!-- Step 1: Basic Post Information -->
        <div x-show="currentStep === 0" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-4"
             x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Post Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Post Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your post title...">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" name="description" required rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe your post content..."></textarea>
                        <div class="mt-1 text-sm text-gray-500">
                            <span id="description-count">0</span> / 500 characters
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Media Type Selection -->
        <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-4"
             x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Media Type</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Text Only Option -->
                    <div class="border-2 border-gray-200 rounded-lg p-6 cursor-pointer hover:border-blue-500 transition-colors"
                         :class="!mediaType ? 'border-blue-500 bg-blue-50' : ''"
                         @click="mediaType = ''">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h4 class="mt-2 text-lg font-medium text-gray-900">Text Only</h4>
                            <p class="mt-1 text-sm text-gray-500">Create a text-only post without media</p>
                        </div>
                    </div>

                    <div class="border-2 border-gray-200 rounded-lg p-6 cursor-pointer hover:border-blue-500 transition-colors"
                         :class="mediaType === 'image' ? 'border-blue-500 bg-blue-50' : ''"
                         @click="mediaType = mediaType === 'image' ? '' : 'image'">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h4 class="mt-2 text-lg font-medium text-gray-900">Images</h4>
                            <p class="mt-1 text-sm text-gray-500">Upload and manage images for your post</p>
                        </div>
                    </div>

                    <div class="border-2 border-gray-200 rounded-lg p-6 cursor-pointer hover:border-blue-500 transition-colors"
                         :class="mediaType === 'video' ? 'border-blue-500 bg-blue-50' : ''"
                         @click="mediaType = mediaType === 'video' ? '' : 'video'">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <h4 class="mt-2 text-lg font-medium text-gray-900">Videos</h4>
                            <p class="mt-1 text-sm text-gray-500">Upload and manage videos for your post</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="media_type" x-model="mediaType">

                <!-- Media Upload Section -->
                <div x-show="mediaType" class="mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-3">
                        Upload <span x-text="mediaType === 'image' ? 'Images' : 'Videos'"></span>
                    </h4>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">
                            Click to upload or drag and drop
                        </p>
                        <p class="text-xs text-gray-500">
                            <span x-text="mediaType === 'image' ? 'PNG, JPG, GIF up to 10MB' : 'MP4, AVI, MOV up to 50MB'"></span>
                        </p>
                        <input type="file" 
                               :accept="mediaType === 'image' ? 'image/*' : 'video/*'"
                               multiple
                               class="hidden" 
                               id="media-upload"
                               @change="handleMediaUpload">
                        <button type="button" 
                                @click="document.getElementById('media-upload').click()"
                                class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Select Files
                        </button>
                    </div>

                    <!-- Media Preview Section -->
                    <div x-show="uploadedFiles.length > 0" class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">
                            Uploaded <span x-text="mediaType === 'image' ? 'Images' : 'Videos'"></span>
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <template x-for="(file, index) in uploadedFiles" :key="index">
                                <div class="relative group">
                                    <div x-show="mediaType === 'image'" class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img :src="file.preview" :alt="file.name" class="w-full h-full object-cover">
                                    </div>
                                    <div x-show="mediaType === 'video'" class="aspect-video rounded-lg overflow-hidden bg-gray-100">
                                        <video :src="file.preview" controls class="w-full h-full object-cover"></video>
                                    </div>
                                    <button type="button" 
                                            @click="removeFile(index)"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-600 truncate" x-text="file.name"></p>
                                        <p class="text-xs text-gray-500" x-text="formatFileSize(file.size)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Platform Selection -->
        <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-4"
             x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Platforms</h3>
                <p class="text-sm text-gray-600 mb-4" x-show="!mediaType">Showing all platforms for text-only posts</p>
                <p class="text-sm text-gray-600 mb-4" x-show="mediaType">Showing platforms that support your selected media type (<span x-text="mediaType === 'image' ? 'Images' : 'Videos'"></span>)</p>
                
                <div class="space-y-3">
                    @php
                        $platformMediaSupport = [
                            'facebook' => ['image', 'video'],
                            'x' => ['image', 'video'],
                            'instagram' => ['image', 'video'],
                            'linkedin' => ['image', 'video'], // LinkedIn now supports videos
                            'youtube' => ['video'], // YouTube only supports video
                        ];
                        
                        $platformIcons = [
                            'facebook' => '<svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                            'x' => '<svg class="w-6 h-6 text-black" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                            'instagram' => '<svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.281-.073-1.689-.073-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z"/></svg>',
                            'linkedin' => '<svg class="w-6 h-6 text-blue-700" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                            'youtube' => '<svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
                        ];
                        
                        $groupedAccounts = [];
                        foreach($socialAccounts as $account) {
                            $platform = $account->platform;
                            if (!isset($groupedAccounts[$platform])) {
                                $groupedAccounts[$platform] = [];
                            }
                            $groupedAccounts[$platform][] = $account;
                        }
                    @endphp
                    
                    @foreach($groupedAccounts as $platform => $accounts)
                        @php
                            // Check if platform exists in media support array
                            $platformSupport = isset($platformMediaSupport[$platform]) ? $platformMediaSupport[$platform] : ['image'];
                            $supportsImage = in_array('image', $platformSupport);
                            $supportsVideo = in_array('video', $platformSupport);
                        @endphp
                        
                        <div x-show="!mediaType || (mediaType === 'image' && {{ $supportsImage ? 'true' : 'false' }}) || (mediaType === 'video' && {{ $supportsVideo ? 'true' : 'false' }})"
                             class="border border-gray-200 rounded-lg p-4">
                            <div class="space-y-2">
                                <!-- Platform header -->
                                <div class="flex items-center">
                                    {!! $platformIcons[$platform] ?? '<svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>' !!}
                                    <span class="ml-2 text-sm font-medium text-gray-900">{{ ucfirst($platform) }}</span>
                                    <span class="ml-2 text-xs text-gray-500">({{ count($accounts) }} account{{ count($accounts) > 1 ? 's' : '' }})</span>
                                </div>
                                
                                <!-- Individual account checkboxes -->
                                @if(count($accounts) > 0)
                                    <div class="ml-8 space-y-2">
                                        @foreach($accounts as $account)
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" name="platforms[]" value="{{ $account->id }}" 
                                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                <div class="ml-3">
                                                    <span class="text-sm text-gray-700">{{ $account->account_name }}</span>
                                                    <span class="ml-2 text-xs text-gray-400">({{ '@'.$account->account_id }})</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    
                    @if(empty($groupedAccounts))
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No social accounts connected</h3>
                            <p class="mt-1 text-sm text-gray-500">Connect your social media accounts to start creating posts.</p>
                            <div class="mt-6">
                                <a href="{{ route('admin.social-accounts.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Connect Account
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Step 4: Platform-Specific Settings -->
        <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-4"
             x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Settings</h3>
                
                <div class="space-y-6">
                    <!-- Facebook Settings -->
                    <div x-show="hasSelectedPlatform('facebook')" class="border-l-4 border-blue-600 pl-4">
                        <h4 class="font-medium text-gray-900 mb-3">Facebook Settings</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Post Type</label>
                                <select name="facebook_post_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="text">Text Post</option>
                                    <option value="photo">Photo Post</option>
                                    <option value="video">Video Post</option>
                                </select>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="facebook_add_link" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Add link to post</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- X Settings -->
                    <div x-show="hasSelectedPlatform('x')" class="border-l-4 border-black pl-4">
                        <h4 class="font-medium text-gray-900 mb-3">X Settings</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tweet Type</label>
                                <select name="x_tweet_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="text">Text Tweet</option>
                                    <option value="media">Media Tweet</option>
                                </select>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="x_add_hashtags" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Auto-add hashtags</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Instagram Settings -->
                    <div x-show="hasSelectedPlatform('instagram')" class="border-l-4 border-pink-600 pl-4">
                        <h4 class="font-medium text-gray-900 mb-3">Instagram Settings</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Content Type</label>
                                <select name="instagram_content_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="post">Post</option>
                                    <option value="story">Story</option>
                                    <option value="reel">Reel</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Caption</label>
                                <textarea name="instagram_caption" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Instagram caption..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- LinkedIn Settings -->
                    <div x-show="hasSelectedPlatform('linkedin')" class="border-l-4 border-blue-700 pl-4">
                        <h4 class="font-medium text-gray-900 mb-3">LinkedIn Settings</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Post Format</label>
                                <select name="linkedin_post_format" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="text">Text Post</option>
                                    <option value="article">Article</option>
                                    <option value="media">Media Post</option>
                                </select>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="linkedin_add_mentions" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Add mentions</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Options -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-medium text-gray-900 mb-3">Schedule Options</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Publish Date</label>
                            <input type="date" name="schedule_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Publish Time</label>
                            <input type="time" name="schedule_time" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Post Status -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-medium text-gray-900 mb-3">Post Status</h4>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select status...</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">
                            Choose when to publish your post. Draft saves it for later, Published posts immediately, and Scheduled posts at your specified time.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex justify-between">
            <button type="button" 
                    x-show="currentStep > 0"
                    @click="previousStep"
                    class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Previous
            </button>

            <button type="button" 
                    x-show="currentStep < steps.length - 1"
                    @click="nextStep"
                    class="ml-auto px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Next
            </button>

            <button type="submit" 
                    x-show="currentStep === steps.length - 1"
                    class="ml-auto px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                Create Post
            </button>
        </div>
    </div>
</div>

