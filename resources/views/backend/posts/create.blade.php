@extends('backend.layouts.app')

@section('title', 'Create Post')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create New Post</h1>
        <p class="mt-1 text-sm text-gray-600">Schedule and publish content across your social media accounts</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('backend.posts._form')
            
            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('admin.posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                    Save Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Character counter for content textarea
        const contentTextarea = document.getElementById('content');
        const charCount = document.getElementById('char-count');
        
        if (contentTextarea && charCount) {
            function updateCharCount() {
                const length = contentTextarea.value.length;
                charCount.textContent = `${length} characters`;
                
                // Change color based on length
                if (length > 280) {
                    charCount.classList.add('text-orange-500');
                    charCount.classList.remove('text-gray-400');
                } else {
                    charCount.classList.remove('text-orange-500');
                    charCount.classList.add('text-gray-400');
                }
            }
            
            contentTextarea.addEventListener('input', updateCharCount);
            updateCharCount(); // Initialize on page load
        }
        
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active classes
                tabButtons.forEach(btn => {
                    btn.classList.remove('bg-blue-600', 'text-white');
                    btn.classList.add('text-gray-600', 'hover:text-gray-800', 'hover:bg-gray-100');
                });
                
                // Hide all content sections
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Add active class to clicked button
                this.classList.add('bg-blue-600', 'text-white');
                this.classList.remove('text-gray-600', 'hover:text-gray-800', 'hover:bg-gray-100');
                
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
        
        // Generate and fill post
        const generateAndFillBtn = document.getElementById('generate-and-fill');
        if (generateAndFillBtn) {
            generateAndFillBtn.addEventListener('click', function() {
                generateContentAndFill();
            });
        }
        
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
        
        // Function to generate content and auto-fill form
        async function generateContentAndFill() {
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
                const response = await fetch('{{ route("admin.posts.generateWithAi") }}', {
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
                
                if (response.ok && data.content) {
                    // Auto-fill the main form fields
                    postContentTextarea.value = data.content;
                    
                    // Auto-generate a title from the topic if title is empty
                    const titleInput = document.getElementById('title');
                    if (!titleInput.value.trim()) {
                        // Create a title from the topic (capitalize first letter, make it engaging)
                        const generatedTitle = topic.charAt(0).toUpperCase() + topic.slice(1).toLowerCase();
                        const titleOptions = [
                            `📝 ${generatedTitle} - Social Media Post`,
                            `✨ ${generatedTitle}`,
                            `🚀 ${generatedTitle} - Quick Update`,
                            `💡 ${generatedTitle} - Tips & Tricks`
                        ];
                        titleInput.value = titleOptions[0]; // Use first option by default
                    }
                    
                    // Update character count
                    updateCharCount();
                    
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4';
                    successDiv.innerHTML = `
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m0 0l2 2m0 0l2 2M7 12l2 2m0 0l2 2M7 12l2 2"></path>
                            </svg>
                            <span class="font-medium">✅ Content generated and auto-filled!</span>
                        </div>
                    `;
                    
                    // Insert success message after the AI panel
                    aiGenerationPanel.parentNode.insertBefore(successDiv, aiGenerationPanel.nextSibling);
                    
                    // Auto-hide the panel after successful generation
                    setTimeout(() => {
                        aiGenerationPanel.classList.add('hidden');
                        successDiv.remove();
                    }, 3000);
                    
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
    });
</script>
@endsection