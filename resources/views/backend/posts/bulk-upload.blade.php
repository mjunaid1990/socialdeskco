@extends('backend.layouts.app')

@section('title', 'Bulk Upload Posts')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Bulk Upload Posts</h1>
        <p class="mt-1 text-sm text-gray-600">Create multiple posts at once and schedule them across your social media accounts</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Upload Methods</h2>
                <div class="space-x-2">
                    <button type="button" id="manualAddBtn" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                        + Add Post Manually
                    </button>
                    <button type="button" id="csvUploadBtn" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition duration-300">
                        📁 Upload CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- CSV Upload Section -->
        <div id="csvUploadSection" class="mb-6 p-4 border-2 border-dashed border-gray-300 rounded-lg transition-all duration-300" style="opacity: 0; max-height: 0; overflow: hidden; margin: 0;">
            <div class="text-center bg-blue-50 p-6 rounded">
                <h3 class="text-lg font-semibold text-blue-800 mb-4">CSV Upload Area</h3>
                <svg class="mx-auto h-12 w-12 text-blue-600" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                    <div class="flex flex-col items-center space-y-3">
                        <label for="csvFile" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition cursor-pointer text-center">
                            <span class="block font-medium">Choose CSV File</span>
                        </label>
                        <input id="csvFile" name="csvFile" type="file" class="hidden" accept=".csv">
                        <div class="mt-3 p-3 bg-gray-50 rounded text-xs text-gray-600">
                            <p class="font-semibold mb-2">CSV Format:</p>
                            <p>title, content, social_account_id, status, scheduled_at, media_files</p>
                            <div class="mt-2">
                                <p class="font-semibold">Example:</p>
                                <code class="block mt-1 p-2 bg-white rounded border">
                                    "My Post Title","This is the post content","1","draft","2024-01-20 14:00:00","https://example.com/image.jpg"
                                </code>
                            </div>
                            <p class="mt-2 text-yellow-600">
                                <strong>Note:</strong> social_account_id must be a valid ID from your connected accounts
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Form -->
        <form id="bulkUploadForm" method="POST" action="{{ route('admin.posts.process-bulk-upload') }}">
            @csrf
            <div id="postsContainer" class="space-y-6">
                <!-- Posts will be added here dynamically -->
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="clearAllBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Clear All
                </button>
                <button type="submit" id="submitBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition duration-300">
                    Upload <span id="postCount">(0)</span> Posts
                </button>
            </div>
        </form>
    </div>

    <!-- Template for new post -->
    <template id="postTemplate">
        <div class="post-item border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-medium text-gray-900">Post <span class="post-number"></span></h3>
                <button type="button" class="remove-post text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="posts[{index}][title]" required maxlength="255" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Social Account *</label>
                    <select name="posts[{index}][social_account_id]" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Account</option>
                        @foreach($socialAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->platform }} - {{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                    <textarea name="posts[{index}][content]" required rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="posts[{index}][status]" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="draft">📝 Draft</option>
                        <option value="scheduled">⏰ Scheduled</option>
                        <option value="published">✅ Published</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Date</label>
                    <input type="datetime-local" name="posts[{index}][scheduled_at]" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for immediate publishing</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Media Files (URLs, one per line)</label>
                    <textarea name="posts[{index}][media_files_text]" rows="2" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="https://example.com/image1.jpg&#10;https://example.com/video1.mp4"></textarea>
                    <input type="hidden" name="posts[{index}][media_files]" value="">
                </div>
            </div>
        </div>
    </template>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Processing Posts</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Please wait while we process your posts...</p>
                <div id="progressInfo" class="mt-2 text-sm text-blue-600"></div>
            </div>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div id="resultsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div id="resultsIcon"></div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Upload Complete</h3>
            <div class="mt-2 px-7 py-3">
                <div id="resultsContent" class="text-sm text-gray-500"></div>
            </div>
            <div class="items-center px-4 py-3">
                <button id="closeResultsBtn" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let postIndex = 0;
let csvVisible = false;

// Smooth CSV toggle function
function toggleCsvUpload() {
    console.log('Toggle CSV upload');
    const csvSection = document.getElementById('csvUploadSection');
    
    if (!csvSection) {
        console.error('CSV section not found');
        return;
    }
    
    csvVisible = !csvVisible;
    
    if (csvVisible) {
        // Show CSV section
        csvSection.style.opacity = '1';
        csvSection.style.maxHeight = '500px';
        csvSection.style.marginBottom = '1.5rem';
        csvSection.style.padding = '1rem';
        console.log('CSV section shown');
    } else {
        // Hide CSV section
        csvSection.style.opacity = '0';
        csvSection.style.maxHeight = '0';
        csvSection.style.marginBottom = '0';
        csvSection.style.padding = '0';
        console.log('CSV section hidden');
    }
}

// Add post function
function addPost() {
    console.log('Adding post with index:', postIndex);
    const template = document.getElementById('postTemplate');
    
    if (!template) {
        console.error('Post template not found');
        return;
    }
    
    const clone = template.content.cloneNode(true);
    const postItem = clone.querySelector('.post-item');
    
    if (!postItem) {
        console.error('Post item not found in template');
        return;
    }
    
    // Replace {index} with actual index
    const html = postItem.innerHTML;
    postItem.innerHTML = html.replace(/{index}/g, postIndex);
    
    // Update post number
    const postNumber = postItem.querySelector('.post-number');
    if (postNumber) {
        postNumber.textContent = postIndex + 1;
    }
    
    // Add remove functionality
    const removeBtn = postItem.querySelector('.remove-post');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            console.log('Remove post clicked');
            this.closest('.post-item').remove();
            updatePostCount();
            showToast('Post removed successfully!', 'info');
        });
    }
    
    // Add to container
    const container = document.getElementById('postsContainer');
    if (container) {
        container.appendChild(clone);
        postIndex++;
        updatePostCount();
        console.log('Post added successfully');
        showToast('Post added successfully!', 'success');
    }
}

// Update post count
function updatePostCount() {
    const count = document.querySelectorAll('.post-item').length;
    const postCount = document.getElementById('postCount');
    if (postCount) {
        postCount.textContent = `(${count})`;
    }
    
    // Update submit button text
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.textContent = `Upload ${count} Posts`;
    }
    
    console.log('Post count updated:', count);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Bulk upload page initialized');
    
    // Add initial post
    addPost();
    
    // Manual add button
    const manualAddBtn = document.getElementById('manualAddBtn');
    if (manualAddBtn) {
        manualAddBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Manual add button clicked');
            addPost();
        });
        console.log('Manual add button listener attached');
    } else {
        console.error('Manual add button not found');
    }
    
    // CSV upload button
    const csvUploadBtn = document.getElementById('csvUploadBtn');
    if (csvUploadBtn) {
        csvUploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('CSV upload button clicked');
            toggleCsvUpload();
        });
        console.log('CSV upload button listener attached');
    } else {
        console.error('CSV upload button not found');
    }
    
    // Clear all button
    const clearAllBtn = document.getElementById('clearAllBtn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Clear all button clicked');
            const container = document.getElementById('postsContainer');
            if (container) {
                container.innerHTML = '';
                postIndex = 0;
                updatePostCount();
                addPost();
                showToast('All posts cleared. Starting fresh!', 'info');
            }
        });
    }
    
    // Form submission
    const bulkUploadForm = document.getElementById('bulkUploadForm');
    if (bulkUploadForm) {
        bulkUploadForm.addEventListener('submit', handleFormSubmit);
    }
    
    // CSV file input
    const csvFile = document.getElementById('csvFile');
    if (csvFile) {
        csvFile.addEventListener('change', handleCsvUpload);
    }
    
    // Close results modal
    const closeResultsBtn = document.getElementById('closeResultsBtn');
    if (closeResultsBtn) {
        closeResultsBtn.addEventListener('click', function() {
            const resultsModal = document.getElementById('resultsModal');
            const resultsSuccess = document.getElementById('resultsSuccess');
            if (resultsModal) resultsModal.classList.add('hidden');
            if (resultsSuccess && resultsSuccess.value === 'true') {
                window.location.href = '{{ route("admin.posts.index") }}';
            }
        });
    }
});

// Handle form submission
function handleFormSubmit(e) {
    e.preventDefault();
    console.log('Form submitted');
    
    // Validate that we have posts to submit
    const postsContainer = document.getElementById('postsContainer');
    const postItems = postsContainer.querySelectorAll('.post-item');
    
    if (postItems.length === 0) {
        showToast('Please add at least one post before submitting.', 'warning');
        return;
    }
    
    console.log('Found post items:', postItems.length);
    
    // Build posts array
    const posts = [];
    let hasErrors = false;
    
    postItems.forEach((item, index) => {
        const postData = {
            title: item.querySelector(`input[name="posts[${index}][title]"]`)?.value || '',
            content: item.querySelector(`textarea[name="posts[${index}][content]"]`)?.value || '',
            social_account_id: item.querySelector(`select[name="posts[${index}][social_account_id]"]`)?.value || '',
            status: item.querySelector(`select[name="posts[${index}][status]"]`)?.value || 'draft',
            scheduled_at: item.querySelector(`input[name="posts[${index}][scheduled_at]"]`)?.value || '',
            media_files: []
        };
        
        // Validate required fields
        if (!postData.title.trim()) {
                    console.error(`Post ${index + 1}: Title is required`);
                    hasErrors = true;
                }
                if (!postData.content.trim()) {
                    console.error(`Post ${index + 1}: Content is required`);
                    hasErrors = true;
                }
                if (!postData.social_account_id) {
                    console.error(`Post ${index + 1}: Social account is required`);
                    hasErrors = true;
                }
        
        // Process media files
        const mediaTextarea = item.querySelector(`textarea[name="posts[${index}][media_files_text]"]`);
        if (mediaTextarea && mediaTextarea.value.trim()) {
            const lines = mediaTextarea.value.trim().split('\n').filter(line => line.trim());
            postData.media_files = lines;
        }
        
        // Update hidden input
        const hiddenInput = item.querySelector(`input[name="posts[${index}][media_files]"]`);
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(postData.media_files);
        }
        
        posts.push(postData);
        console.log(`Post ${index + 1} data:`, postData);
    });
    
    if (hasErrors) {
        showToast('Please fill in all required fields (title, content, social account).', 'error');
        return;
    }
    
    // Show loading modal
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) {
        loadingModal.classList.remove('hidden');
    }
    
    // Create form data
    const formData = new FormData();
    
    // Add posts as individual array elements (not JSON string)
    posts.forEach((post, index) => {
        Object.keys(post).forEach(key => {
            if (key === 'media_files' && Array.isArray(post[key])) {
                post[key].forEach((mediaFile, mediaIndex) => {
                    formData.append(`posts[${index}][${key}][${mediaIndex}]`, mediaFile);
                });
            } else {
                formData.append(`posts[${index}][${key}]`, post[key]);
            }
        });
    });
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    console.log('Submitting posts:', posts);
    console.log('FormData entries:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
    }
    
    fetch('{{ route("admin.posts.process-bulk-upload") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response received:', data);
        if (loadingModal) loadingModal.classList.add('hidden');
        showResults(data);
    })
    .catch(error => {
        console.error('Error:', error);
        if (loadingModal) loadingModal.classList.add('hidden');
        showToast('An error occurred while processing your posts: ' + error.message, 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = `Upload ${postItems.length} Posts`;
        }
    });
}

// Handle CSV file upload
function handleCsvUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    console.log('CSV file selected:', file.name, 'Size:', file.size);
    
    // Validate file type
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showToast('Please select a valid CSV file.', 'error');
        e.target.value = ''; // Clear the input
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(event) {
        try {
            const csv = event.target.result;
            console.log('CSV content length:', csv.length);
            
            const lines = csv.split('\n').filter(line => line.trim());
            console.log('Total lines:', lines.length);
            
            if (lines.length < 2) {
                showToast('CSV file must contain at least a header row and one data row.', 'error');
                return;
            }
            
            // Clear existing posts
            const container = document.getElementById('postsContainer');
            if (container) {
                container.innerHTML = '';
                postIndex = 0;
            }
            
            // Process each row
            let successCount = 0;
            for (let i = 1; i < lines.length; i++) {
                const values = lines[i].split(',').map(v => v.trim().replace(/^"|"$/g, '')); // Remove quotes
                console.log(`Row ${i} values:`, values);
                
                if (values.length >= 3) { // At least title, content, social_account_id
                    addPost();
                    const postItem = document.querySelectorAll('.post-item')[successCount];
                    
                    // Fill form fields safely
                    try {
                        const titleInput = postItem.querySelector(`input[name="posts[${successCount}][title]"]`);
                        if (titleInput && values[0]) titleInput.value = values[0];
                        
                        const contentTextarea = postItem.querySelector(`textarea[name="posts[${successCount}][content]"]`);
                        if (contentTextarea && values[1]) contentTextarea.value = values[1];
                        
                        const accountSelect = postItem.querySelector(`select[name="posts[${successCount}][social_account_id]"]`);
                        if (accountSelect && values[2]) accountSelect.value = values[2];
                        
                        const statusSelect = postItem.querySelector(`select[name="posts[${successCount}][status]"]`);
                        if (statusSelect) statusSelect.value = values[3] || 'draft';
                        
                        if (values[4]) {
                            const scheduledInput = postItem.querySelector(`input[name="posts[${successCount}][scheduled_at]"]`);
                            if (scheduledInput) scheduledInput.value = values[4];
                        }
                        
                        if (values[5]) {
                            const mediaTextarea = postItem.querySelector(`textarea[name="posts[${successCount}][media_files_text]"]`);
                            if (mediaTextarea) mediaTextarea.value = values[5].replace(/;/g, '\n');
                        }
                        
                        successCount++;
                    } catch (error) {
                        console.error(`Error filling post ${successCount}:`, error);
                    }
                }
            }
            
            updatePostCount();
            console.log(`Successfully processed ${successCount} posts from CSV`);
            
            // Show success toast
            if (successCount > 0) {
                showToast(`Successfully imported ${successCount} posts from CSV!`, 'success');
            }
            
            // Show success message
            if (successCount > 0) {
                // Hide CSV section after successful upload
                setTimeout(() => {
                    toggleCsvUpload();
                }, 1000);
                
                // Clear file input
                e.target.value = '';
            }
            
        } catch (error) {
            console.error('Error processing CSV:', error);
            showToast('Error processing CSV file. Please check the file format.', 'error');
        }
    };
    
    reader.onerror = function() {
        console.error('Error reading file');
        showToast('Error reading CSV file.', 'error');
    };
    
    reader.readAsText(file);
}

// Show results
function showResults(data) {
    const modal = document.getElementById('resultsModal');
    const icon = document.getElementById('resultsIcon');
    const content = document.getElementById('resultsContent');
    
    if (!modal || !icon || !content) {
        console.error('Results modal elements not found');
        return;
    }
    
    if (data.success && data.errors.length === 0) {
        icon.innerHTML = '<svg class="mx-auto h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        content.innerHTML = `<p class="text-green-600 font-semibold">Successfully processed ${data.processed} posts!</p>`;
        const resultsSuccess = document.getElementById('resultsSuccess');
        if (resultsSuccess) resultsSuccess.value = 'true';
        showToast(`Successfully processed ${data.processed} posts!`, 'success');
    } else {
        icon.innerHTML = '<svg class="mx-auto h-12 w-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>';
        
        let errorHtml = `<p class="text-yellow-600 font-semibold">Processed ${data.processed} posts with ${data.errors.length} errors:</p>`;
        if (data.errors.length > 0) {
            errorHtml += '<ul class="mt-2 text-left text-sm">';
            Object.entries(data.errors).forEach(([index, error]) => {
                errorHtml += `<li>Post ${parseInt(index) + 1}: ${error}</li>`;
            });
            errorHtml += '</ul>';
        }
        content.innerHTML = errorHtml;
        const resultsSuccess = document.getElementById('resultsSuccess');
        if (resultsSuccess) resultsSuccess.value = 'false';
        showToast(`Processed ${data.processed} posts with ${data.errors.length} errors`, 'warning');
    }
    
    modal.classList.remove('hidden');
}
</script>

<input type="hidden" id="resultsSuccess" value="false">
@endpush
