<script>
function multiStepForm() {
    return {
        currentStep: 0,
        mediaType: '',
        selectedPlatforms: [],
        uploadedFiles: [],
        steps: [
            { title: 'Basic Info', validation: ['title', 'description'] },
            { title: 'Media Type', validation: ['media_type'] },
            { title: 'Platforms', validation: ['platforms'] },
            { title: 'Settings', validation: ['status'] }
        ],

        init() {
            // Character counter for description
            const description = document.getElementById('description');
            const counter = document.getElementById('description-count');
            
            if (description && counter) {
                description.addEventListener('input', function() {
                    counter.textContent = this.value.length;
                    if (this.value.length > 500) {
                        this.value = this.value.substring(0, 500);
                        counter.textContent = 500;
                    }
                });
            }

            // Track selected platforms
            this.$watch('currentStep', () => {
                if (this.currentStep === 2) {
                    this.updateSelectedPlatforms();
                }
            });

            // Clear media files when media type changes
            this.$watch('mediaType', (newType, oldType) => {
                if (newType !== oldType) {
                    this.clearMediaFiles();
                }
            });
        },

        nextStep() {
            if (this.validateCurrentStep()) {
                if (this.currentStep < this.steps.length - 1) {
                    this.currentStep++;
                }
            }
        },

        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        validateCurrentStep() {
            const currentValidation = this.steps[this.currentStep].validation;
            
            for (let field of currentValidation) {
                if (field === 'title') {
                    const title = document.getElementById('title');
                    if (!title || !title.value.trim()) {
                        this.showError('Title is required');
                        if (title) title.focus();
                        return false;
                    }
                }
                
                if (field === 'description') {
                    const description = document.getElementById('description');
                    if (!description || !description.value.trim()) {
                        this.showError('Description is required');
                        if (description) description.focus();
                        return false;
                    }
                }
                
                if (field === 'media_type') {
                    // If no media type is selected (text-only), that's fine
                    if (!this.mediaType) {
                        return true; // Allow proceeding with text-only post
                    }
                    
                    // If media type is selected, ensure files are uploaded
                    if (this.mediaType && this.uploadedFiles.length === 0) {
                        this.showError(`Please upload at least one ${this.mediaType} file or select "Text Only"`);
                        return false;
                    }
                }
                
                if (field === 'platforms') {
                    const platforms = document.querySelectorAll('input[name="platforms[]"]:checked');
                    if (platforms.length === 0) {
                        this.showError('Please select at least one platform');
                        return false;
                    }
                }
                
                if (field === 'status') {
                    const status = document.getElementById('status');
                    if (!status || !status.value) {
                        this.showError('Please select a post status');
                        if (status) status.focus();
                        return false;
                    }
                }
            }
            
            return true;
        },

        updateSelectedPlatforms() {
            const platforms = document.querySelectorAll('input[name="platforms[]"]:checked');
            this.selectedPlatforms = Array.from(platforms).map(input => input.value);
        },

        hasSelectedPlatform(platformName) {
            // Get selected platform checkboxes
            const selectedCheckboxes = document.querySelectorAll('input[name="platforms[]"]:checked');
            
            // Check if any selected checkbox belongs to the specified platform
            for (let checkbox of selectedCheckboxes) {
                const container = checkbox.closest('.border-gray-200');
                if (!container) continue;
                
                // Look for platform icon with specific color class
                const colorClass = this.getPlatformColor(platformName);
                const platformIcon = container.querySelector(`.text-${colorClass}`);
                
                if (platformIcon) {
                    return true;
                }
            }
            
            return false;
        },

        getPlatformColor(platformName) {
            const colors = {
                'facebook': 'blue-600',
                'instagram': 'pink-600', 
                'x': 'black',
                'linkedin': 'blue-700',
                'youtube': 'red-600'
            };
            return colors[platformName] || 'gray-600';
        },

        handleMediaUpload(event) {
            const files = event.target.files;
            const newFiles = [];
            
            for (let file of files) {
                // Create preview URL
                const preview = URL.createObjectURL(file);
                
                newFiles.push({
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    preview: preview,
                    file: file
                });
            }
            
            // Add to uploaded files array
            this.uploadedFiles = [...this.uploadedFiles, ...newFiles];
            
            console.log('Media files selected:', files);
        },

        // Clear uploaded files when media type changes
        clearMediaFiles() {
            // Revoke all object URLs to free memory
            this.uploadedFiles.forEach(fileObj => {
                URL.revokeObjectURL(fileObj.preview);
            });
            this.uploadedFiles = [];
            
            // Clear file input
            const fileInput = document.getElementById('media-upload');
            if (fileInput) {
                fileInput.value = '';
            }
        },

        removeFile(index) {
            // Revoke the object URL to free memory
            URL.revokeObjectURL(this.uploadedFiles[index].preview);
            
            // Remove from array
            this.uploadedFiles.splice(index, 1);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        submitForm() {
            if (this.validateCurrentStep()) {
                // Get the parent form (the main form that contains this multi-step form)
                const form = this.$el.closest('form');
                if (!form) {
                    this.showError('Form not found');
                    return;
                }
                
                // Create FormData for proper file upload handling
                const formData = new FormData(form);
                
                // Add uploaded files to FormData
                this.uploadedFiles.forEach((fileObj, index) => {
                    formData.append('media[]', fileObj.file);
                });
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating Post...';
                
                // Submit via AJAX
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    }
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Redirect to posts index on success
                        window.location.href = data.redirect || '/admin/posts';
                    } else {
                        this.showError(data.message || 'Failed to create post');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        // Keep user on current step - don't reset form
                        return false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showError(error.message || 'An error occurred while creating the post');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    // Keep user on current step - don't reset form
                    return false;
                });
            }
        },

        showError(message) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    }
}
</script>
