@extends('backend.layouts.app')

@section('title', 'Edit Post')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Post</h1>
        <p class="mt-1 text-sm text-gray-600">Update your scheduled or published post</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.posts.update', $post->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('backend.posts._form')
            
            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('admin.posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                    Update Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active classes
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'text-blue-600', 'border-blue-600');
                    btn.classList.add('text-gray-500', 'border-transparent');
                });
                
                // Hide all content sections
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Add active class to clicked button
                this.classList.add('active', 'text-blue-600', 'border-blue-600');
                this.classList.remove('text-gray-500', 'border-transparent');
                
                // Show corresponding content
                const targetId = this.getAttribute('data-target');
                document.getElementById(targetId).classList.remove('hidden');
            });
        });
        
        const toggleAiPanelBtn = document.getElementById('toggle-ai-panel');
        const aiGenerationPanel = document.getElementById('ai-generation-panel');
        const closeAiPanelBtn = document.getElementById('close-ai-panel');
        const generateSingleBtn = document.getElementById('generate-single');
        const generateVariationsBtn = document.getElementById('generate-variations');
        const generateImageBtn = document.getElementById('generate-image');
        const generateImageFromContentBtn = document.getElementById('generate-image-from-content');
        const generateCombinedBtn = document.getElementById('generate-combined');
        const applyContentBtn = document.getElementById('apply-content');
        const applyImageBtn = document.getElementById('apply-image');
        const regenerateContentBtn = document.getElementById('regenerate-content');
        const aiLoading = document.getElementById('ai-loading');
        const aiResults = document.getElementById('ai-results');
        const generatedContentResultDiv = document.getElementById('generated-content-result');
        const generatedImageResultDiv = document.getElementById('generated-image-result');
        const generatedImage = document.getElementById('generated-image');
        const variationsContainer = document.getElementById('variations-container');
        const variationsList = document.getElementById('variations-list');
        const postContentTextarea = document.getElementById('content');
        const postMediaInput = document.getElementById('media');
        
        // Toggle AI panel visibility
        toggleAiPanelBtn.addEventListener('click', function() {
            aiGenerationPanel.classList.toggle('hidden');
        });
        
        // Close AI panel
        closeAiPanelBtn.addEventListener('click', function() {
            aiGenerationPanel.classList.add('hidden');
        });
        
        // Generate single post
        generateSingleBtn.addEventListener('click', function() {
            generateContent(false);
        });
        
        // Generate variations
        generateVariationsBtn.addEventListener('click', function() {
            generateContent(true);
        });
        
        // Generate image
        generateImageBtn.addEventListener('click', function() {
            generateImage();
        });
        
        // Generate image from content
        generateImageFromContentBtn.addEventListener('click', function() {
            generateImageFromContent();
        });
        
        // Generate combined content and image
        generateCombinedBtn.addEventListener('click', function() {
            generateCombined();
        });
        
        // Apply content to main textarea
        applyContentBtn.addEventListener('click', function() {
            if (generatedContentResultDiv.textContent.trim() !== '' && !generatedContentResultDiv.classList.contains('hidden')) {
                postContentTextarea.value = generatedContentResultDiv.textContent;
            }
        });
        
        // Apply image to post
        applyImageBtn.addEventListener('click', function() {
            const imageUrl = generatedImage.src;
            if (!imageUrl || imageUrl === window.location.href) {
                alert('No image to apply');
                return;
            }
            
            // Add the image to the media files input
            const mediaFiles = postContentTextarea.closest('form').querySelector('#media');
            
            // For now, we'll just add the image URL to the media_files input if it exists
            // In a real scenario, we'd want to add it to the actual file input
            const mediaFilesArray = postContentTextarea.closest('form').querySelector('input[name="media_files[]"]');
            if (!mediaFilesArray) {
                // Create hidden input for the media file
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'media_files[]';
                hiddenInput.value = imageUrl;
                hiddenInput.id = 'ai-generated-image';
                postContentTextarea.closest('form').appendChild(hiddenInput);
            } else {
                mediaFilesArray.value = imageUrl;
            }
            
            alert('Image has been added to the post');
        });
        
        // Regenerate content
        regenerateContentBtn.addEventListener('click', function() {
            const activeTab = document.querySelector('.tab-button.active');
            const tabTarget = activeTab.getAttribute('data-target');
            
            if (tabTarget === 'content-section') {
                const isVariation = variationsContainer.classList.contains('hidden') ? false : true;
                generateContent(isVariation);
            } else if (tabTarget === 'image-section') {
                generateImage();
            }
        });
        
        // Function to generate content
        async function generateContent(isVariation) {
            const topic = document.getElementById('ai-topic').value.trim();
            const platform = document.getElementById('ai-platform').value;
            const tone = document.getElementById('ai-tone').value;
            const maxLength = document.getElementById('ai-max-length').value;
            const keywordsText = document.getElementById('ai-keywords').value;
            
            if (!topic) {
                alert('Please enter a topic for AI generation');
                return;
            }
            
            // Parse keywords
            let keywords = [];
            if (keywordsText) {
                keywords = keywordsText.split(',').map(k => k.trim()).filter(k => k !== '');
            }
            
            // Show loading, hide results
            aiLoading.classList.remove('hidden');
            aiResults.classList.add('hidden');
            generatedContentResultDiv.classList.add('hidden');
            generatedImageResultDiv.classList.add('hidden');
            variationsContainer.classList.add('hidden');
            
            try {
                const url = isVariation ? '{{ route("admin.posts.generateVariationsWithAi") }}' : '{{ route("admin.posts.generateWithAi") }}';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        topic: topic,
                        platform: platform,
                        tone: tone,
                        max_length: parseInt(maxLength),
                        keywords: keywords
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    if (isVariation && data.variations) {
                        displayVariations(data.variations);
                    } else if (data.content) {
                        displaySingleContent(data.content);
                    }
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate content'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating content: ' + error.message);
            } finally {
                aiLoading.classList.add('hidden');
            }
        }
        
        // Function to generate image
        async function generateImage() {
            const prompt = document.getElementById('ai-image-prompt').value.trim();
            const size = document.getElementById('ai-image-size').value;
            const style = document.getElementById('ai-image-style').value;
            const quality = document.getElementById('ai-image-quality').value;
            const topic = document.getElementById('ai-image-topic').value;
            
            if (!prompt) {
                alert('Please enter a prompt for image generation');
                return;
            }
            
            // Show loading, hide results
            aiLoading.classList.remove('hidden');
            aiResults.classList.add('hidden');
            generatedContentResultDiv.classList.add('hidden');
            generatedImageResultDiv.classList.add('hidden');
            variationsContainer.classList.add('hidden');
            
            try {
                let url, body;
                
                if (topic) {
                    // Use the combined endpoint
                    url = '{{ route("admin.posts.generateImageForPostWithAi") }}';
                    body = JSON.stringify({
                        content: prompt,  // Using prompt as content for image generation
                        topic: topic,
                        platform: document.getElementById('ai-platform').value || null,
                        size: size
                    });
                } else {
                    // Use the simple image generation endpoint
                    url = '{{ route("admin.posts.generateImageWithAi") }}';
                    body = JSON.stringify({
                        prompt: prompt,
                        size: size,
                        style: style,
                        quality: quality
                    });
                }
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: body
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    if (data.image) {
                        displayImage(data.image.url);
                    }
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate image'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating image: ' + error.message);
            } finally {
                aiLoading.classList.add('hidden');
            }
        }
        
        // Function to generate image from content
        async function generateImageFromContent() {
            const content = postContentTextarea.value.trim();
            const topic = document.getElementById('ai-combined-topic').value.trim() || 'General topic';
            const platform = document.getElementById('ai-combined-platform').value;
            const size = document.getElementById('ai-combined-size').value;
            
            if (!content) {
                alert('Please enter some content first or switch to the Content tab to generate content');
                return;
            }
            
            // Show loading, hide results
            aiLoading.classList.remove('hidden');
            aiResults.classList.add('hidden');
            generatedContentResultDiv.classList.add('hidden');
            generatedImageResultDiv.classList.add('hidden');
            variationsContainer.classList.add('hidden');
            
            try {
                const url = '{{ route("admin.posts.generateImageForPostWithAi") }}';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: content,
                        topic: topic,
                        platform: platform,
                        size: size
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    if (data.image) {
                        displayImage(data.image.url);
                    }
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate image from content'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating image from content: ' + error.message);
            } finally {
                aiLoading.classList.add('hidden');
            }
        }
        
        // Function to generate combined content and image
        async function generateCombined() {
            const topic = document.getElementById('ai-combined-topic').value.trim();
            const platform = document.getElementById('ai-combined-platform').value;
            const tone = document.getElementById('ai-combined-tone').value;
            const size = document.getElementById('ai-combined-size').value;
            
            if (!topic) {
                alert('Please enter a topic for combined generation');
                return;
            }
            
            // Show loading, hide results
            aiLoading.classList.remove('hidden');
            aiResults.classList.add('hidden');
            generatedContentResultDiv.classList.add('hidden');
            generatedImageResultDiv.classList.add('hidden');
            variationsContainer.classList.add('hidden');
            
            try {
                // First generate content
                const contentResponse = await fetch('{{ route("admin.posts.generateWithAi") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        topic: topic,
                        platform: platform,
                        tone: tone,
                        max_length: 280,
                        keywords: []
                    })
                });
                
                const contentData = await contentResponse.json();
                
                if (!contentResponse.ok) {
                    alert('Error generating content: ' + (contentData.error || 'Failed to generate content'));
                    return;
                }
                
                // Then generate image based on the content
                const imageResponse = await fetch('{{ route("admin.posts.generateImageForPostWithAi") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: contentData.content,
                        topic: topic,
                        platform: platform,
                        size: size
                    })
                });
                
                const imageData = await imageResponse.json();
                
                if (imageResponse.ok) {
                    displayCombined(contentData.content, imageData.image.url);
                } else {
                    alert('Error: ' + (imageData.error || 'Failed to generate image'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating combined content and image: ' + error.message);
            } finally {
                aiLoading.classList.add('hidden');
            }
        }
        
        // Display single content
        function displaySingleContent(content) {
            generatedContentResultDiv.textContent = content;
            generatedContentResultDiv.classList.remove('hidden');
            generatedImageResultDiv.classList.add('hidden');
            variationsContainer.classList.add('hidden');
            aiResults.classList.remove('hidden');
        }
        
        // Display variations
        function displayVariations(variations) {
            variationsList.innerHTML = '';
            
            variations.forEach((variation, index) => {
                const variationDiv = document.createElement('div');
                variationDiv.className = 'border border-gray-300 rounded p-3';
                
                const contentP = document.createElement('p');
                contentP.className = 'mb-2';
                contentP.textContent = variation;
                
                const selectBtn = document.createElement('button');
                selectBtn.type = 'button';
                selectBtn.className = 'text-sm bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700';
                selectBtn.textContent = 'Select';
                selectBtn.onclick = function() {
                    generatedContentResultDiv.textContent = variation;
                };
                
                variationDiv.appendChild(contentP);
                variationDiv.appendChild(selectBtn);
                variationsList.appendChild(variationDiv);
            });
            
            generatedContentResultDiv.textContent = variations[0]; // Set first as default
            generatedContentResultDiv.classList.remove('hidden');
            generatedImageResultDiv.classList.add('hidden');
            variationsContainer.classList.remove('hidden');
            aiResults.classList.remove('hidden');
        }
        
        // Display image
        function displayImage(imageUrl) {
            generatedImage.src = imageUrl;
            generatedImageResultDiv.classList.remove('hidden');
            generatedContentResultDiv.classList.add('hidden');
            variationsContainer.classList.add('hidden');
            aiResults.classList.remove('hidden');
        }
        
        // Display combined content and image
        function displayCombined(content, imageUrl) {
            generatedContentResultDiv.textContent = content;
            generatedContentResultDiv.classList.remove('hidden');
            
            generatedImage.src = imageUrl;
            generatedImageResultDiv.classList.remove('hidden');
            
            variationsContainer.classList.add('hidden');
            aiResults.classList.remove('hidden');
        }
        
        // Function to remove existing media files
        window.removeExistingMedia = function(index) {
            const mediaItem = document.querySelector(`.existing-media-item[data-index="${index}"]`);
            if (mediaItem) {
                // Add fade out animation
                mediaItem.style.transition = 'opacity 0.3s ease-out';
                mediaItem.style.opacity = '0';
                
                setTimeout(() => {
                    mediaItem.remove();
                    
                    // Check if there are any remaining media files
                    const remainingItems = document.querySelectorAll('.existing-media-item');
                    if (remainingItems.length === 0) {
                        const existingMediaSection = document.getElementById('existing-media-files').parentElement;
                        existingMediaSection.remove();
                    }
                }, 300);
            }
        };
        
        // Function to remove newly uploaded files (from create page)
        window.removeFile = function(index) {
            const mediaInput = document.getElementById('media');
            const dt = new DataTransfer();
            const files = Array.from(mediaInput.files);
            files.splice(index, 1);
            files.forEach(file => dt.items.add(file));
            mediaInput.files = dt.files;
            handleFiles(mediaInput.files);
        };
        
        // Function to handle new file uploads (for edit page)
        function handleFiles(files) {
            const filePreviewContainer = document.getElementById('file-previews');
            if (!filePreviewContainer) {
                // Create preview container if it doesn't exist
                const container = document.createElement('div');
                container.className = 'mt-4 grid grid-cols-2 md:grid-cols-4 gap-4';
                container.id = 'file-previews';
                document.querySelector('.border-dashed').parentNode.insertBefore(container, document.querySelector('.border-dashed').nextSibling.nextSibling);
            }
            
            // Clear existing previews
            filePreviewContainer.innerHTML = '';
            
            if (files.length === 0) return;
            
            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = createImagePreview(e.target.result, file.name, index);
                        filePreviewContainer.appendChild(previewDiv);
                    };
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = createVideoPreview(e.target.result, file.name, index);
                        filePreviewContainer.appendChild(previewDiv);
                    };
                    reader.readAsDataURL(file);
                } else {
                    const previewDiv = createFilePreview(file.name, file.size, index);
                    filePreviewContainer.appendChild(previewDiv);
                }
            });
        }
        
        // Preview creation functions
        function createImagePreview(src, fileName, index) {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.innerHTML = `
                <div class="aspect-square rounded-lg overflow-hidden border border-gray-200">
                    <img src="${src}" alt="${fileName}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                        <button type="button" onclick="removeFile(${index})" class="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-600 truncate">${fileName}</p>
            `;
            return div;
        }
        
        function createVideoPreview(src, fileName, index) {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.innerHTML = `
                <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
                    <video src="${src}" class="w-full h-full object-cover" controls></video>
                    <div class="absolute top-2 right-2">
                        <button type="button" onclick="removeFile(${index})" class="bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-all duration-200">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-600 truncate">${fileName}</p>
            `;
            return div;
        }
        
        function createFilePreview(fileName, fileSize, index) {
            const div = document.createElement('div');
            div.className = 'relative group';
            const fileIcon = getFileIcon(fileName);
            const fileSizeText = formatFileSize(fileSize);
            div.innerHTML = `
                <div class="aspect-square rounded-lg border border-gray-200 bg-gray-50 flex flex-col items-center justify-center p-4">
                    <div class="text-3xl mb-2">${fileIcon}</div>
                    <p class="text-xs text-gray-600 text-center truncate w-full">${fileName}</p>
                    <p class="text-xs text-gray-400">${fileSizeText}</p>
                    <button type="button" onclick="removeFile(${index})" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-all duration-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            return div;
        }
        
        function getFileIcon(fileName) {
            const extension = fileName.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': '📄',
                'doc': '📝', 'docx': '📝',
                'xls': '📊', 'xlsx': '📊',
                'ppt': '📈', 'pptx': '📈',
                'txt': '📃',
                'zip': '🗜️', 'rar': '🗜️',
                'mp3': '🎵', 'wav': '🎵',
                'default': '📎'
            };
            return iconMap[extension] || iconMap['default'];
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Handle file selection for edit page
        const mediaInput = document.getElementById('media');
        if (mediaInput) {
            mediaInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });
        }
        
        // Handle drag and drop for edit page
        const dropZone = document.querySelector('.border-dashed');
        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                dropZone.classList.add('border-blue-400', 'bg-blue-50');
            }
            
            function unhighlight(e) {
                dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            }
            
            dropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }, false);
        }
    });
</script>
@endsection