<div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-x-4"
     x-transition:enter-end="opacity-100 transform translate-x-0">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Media Type</h3>
        <p class="text-sm text-gray-600 mb-4" x-show="getCommonMediaTypes().length > 0">
            Showing media types supported by all selected platforms
        </p>
        <p class="text-sm text-yellow-600 mb-4" x-show="getCommonMediaTypes().length === 0 && selectedAccounts.length > 0">
            No common media types found between selected platforms. Please adjust your selection.
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Text Only Option -->
            <div class="border-2 rounded-lg p-6 transition-colors"
                 :class="{
                     'border-blue-500 bg-blue-50 cursor-pointer': mediaType === 'text',
                     'border-gray-200 hover:border-blue-300 cursor-pointer': mediaType !== 'text' && getCommonMediaTypes().includes('text'),
                     'border-gray-300 bg-gray-100 opacity-60 cursor-not-allowed': !getCommonMediaTypes().includes('text')
                 }"
                 @click="getCommonMediaTypes().includes('text') && (mediaType = mediaType === 'text' ? '' : 'text')">
                <div class="text-center relative">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h4 class="mt-2 text-lg font-medium text-gray-900">Text Only</h4>
                    <p class="mt-1 text-sm text-gray-500">Create a text-only post without media</p>
                    <div x-show="!getCommonMediaTypes().includes('text') && selectedAccounts.length > 0" class="mt-2">
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="mr-1.5 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Not supported
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-2 rounded-lg p-6 transition-colors"
                 :class="{
                     'border-blue-500 bg-blue-50 cursor-pointer': mediaType === 'image',
                     'border-gray-200 hover:border-blue-300 cursor-pointer': mediaType !== 'image' && getCommonMediaTypes().includes('image'),
                     'border-gray-300 bg-gray-100 opacity-60 cursor-not-allowed': !getCommonMediaTypes().includes('image')
                 }"
                 @click="getCommonMediaTypes().includes('image') && (mediaType = mediaType === 'image' ? '' : 'image')">
                <div class="text-center relative">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h4 class="mt-2 text-lg font-medium text-gray-900">Images</h4>
                    <p class="mt-1 text-sm text-gray-500">Upload and manage images for your post</p>
                    <div x-show="!getCommonMediaTypes().includes('image') && selectedAccounts.length > 0" class="mt-2">
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="mr-1.5 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Not supported
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-2 rounded-lg p-6 transition-colors"
                 :class="{
                     'border-blue-500 bg-blue-50 cursor-pointer': mediaType === 'video',
                     'border-gray-200 hover:border-blue-300 cursor-pointer': mediaType !== 'video' && getCommonMediaTypes().includes('video'),
                     'border-gray-300 bg-gray-100 opacity-60 cursor-not-allowed': !getCommonMediaTypes().includes('video')
                 }"
                 @click="getCommonMediaTypes().includes('video') && (mediaType = mediaType === 'video' ? '' : 'video')">
                <div class="text-center relative">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h4 class="mt-2 text-lg font-medium text-gray-900">Videos</h4>
                    <p class="mt-1 text-sm text-gray-500">Upload and manage videos for your post</p>
                    <div x-show="!getCommonMediaTypes().includes('video') && selectedAccounts.length > 0" class="mt-2">
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="mr-1.5 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Not supported
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="media_type" x-model="mediaType">

        <!-- Media Upload Section -->
        <div x-show="mediaType && mediaType !== 'text'" class="mt-6">
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
            <div x-show="mediaType && mediaType !== 'text' && uploadedFiles.length > 0" class="mt-6">
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