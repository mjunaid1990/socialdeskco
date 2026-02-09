<script>
function multiStepForm(accountPlatforms) {
    // Handle case when accountPlatforms is undefined or invalid
    if (!accountPlatforms || typeof accountPlatforms !== 'object') {
        accountPlatforms = {};
    }
    
    return {
        currentStep: 0,
        mediaType: '',
        selectedAccounts: [],
        uploadedFiles: [],
        accountPlatforms: accountPlatforms, // Pass account to platform mapping
        steps: [
            { title: 'Accounts', validation: ['selected_accounts'] },
            { title: 'Media Type', validation: ['media_type'] },
            { title: 'Basic Info', validation: ['title', 'description'] },
            { title: 'Platform Fields', validation: ['status'] }
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

            // Track selected accounts
            this.$watch('currentStep', () => {
                if (this.currentStep === 1) {
                    // Reset media type when returning to account selection
                    this.mediaType = '';
                }
                if (this.currentStep === 2) {
                    // Initialize character counter for description
                    const description = document.getElementById('description');
                    const counter = document.getElementById('description-count');
                    if (description && counter) {
                        counter.textContent = description.value.length;
                    }
                }
                if (this.currentStep === 3) {
                    // Initialize TikTok caption counter
                    const tiktokCaption = document.querySelector('textarea[name="tiktok_caption"]');
                    const tiktokCounter = document.getElementById('tiktok-caption-count');
                    if (tiktokCaption && tiktokCounter) {
                        tiktokCounter.textContent = tiktokCaption.value.length;
                        tiktokCaption.addEventListener('input', function() {
                            tiktokCounter.textContent = this.value.length;
                            if (this.value.length > 2200) {
                                this.value = this.value.substring(0, 2200);
                                tiktokCounter.textContent = 2200;
                            }
                        });
                    }
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
                if (field === 'selected_accounts') {
                    if (!this.validateAccounts()) {
                        return false;
                    }
                }
                
                if (field === 'media_type') {
                    if (!this.validateMediaType()) {
                        return false;
                    }
                }
                
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
                
                if (field === 'status') {
                    const status = document.getElementById('post_status');
                    if (!status || !status.value) {
                        this.showError('Post status is required');
                        if (status) status.focus();
                        return false;
                    }
                    
                    // If status is scheduled, validate scheduled time
                    if (status.value === 'scheduled') {
                        const scheduledAt = document.getElementById('scheduled_at');
                        if (!scheduledAt || !scheduledAt.value) {
                            this.showError('Schedule date and time is required when status is scheduled');
                            if (scheduledAt) scheduledAt.focus();
                            return false;
                        }
                        
                        // Validate that scheduled time is in the future
                        const scheduledTime = new Date(scheduledAt.value);
                        const now = new Date();
                        if (scheduledTime <= now) {
                            this.showError('Schedule time must be in the future');
                            if (scheduledAt) scheduledAt.focus();
                            return false;
                        }
                    }
                }
            }
            
            return true;
        },

        // Get common media types supported by all selected accounts
        getCommonMediaTypes() {
            if (this.selectedAccounts.length === 0) return ['text', 'image', 'video'];
            
            // This would need to be populated from server-side data
            // For now, we'll implement a simplified version
            const platformSupport = {
                'facebook': ['text', 'image', 'video'],
                'x': ['text', 'image', 'video'],
                'instagram': ['image', 'video'],
                'linkedin': ['text', 'image', 'video'],
                'youtube': ['video'],
                'tiktok': ['video']
            };
            
            // Get platforms of selected accounts
            const selectedPlatforms = this.selectedAccounts.map(accountId => {
                // This would need to map accountId to platform
                // For demo purposes, we'll assume a mapping
                return this.getAccountPlatform(accountId);
            });
            
            // Find intersection of supported media types
            let commonTypes = ['text', 'image', 'video']; // Start with all types
            
            selectedPlatforms.forEach(platform => {
                if (platformSupport[platform]) {
                    commonTypes = commonTypes.filter(type => 
                        platformSupport[platform].includes(type)
                    );
                }
            });
            
            return commonTypes;
        },

        // Get platform for a given account ID
        getAccountPlatform(accountId) {
            return this.accountPlatforms[accountId] || 'unknown';
        },

        // Validate account selection
        validateAccounts() {
            if (this.selectedAccounts.length === 0) {
                this.showError('Please select at least one social account');
                return false;
            }
            return true;
        },

        // Validate media type selection
        validateMediaType() {
            if (this.getCommonMediaTypes().length === 0) {
                this.showError('No common media types available for selected platforms');
                return false;
            }
            
            if (!this.mediaType) {
                this.showError('Please select a media type');
                return false;
            }
            
            if (!this.getCommonMediaTypes().includes(this.mediaType)) {
                this.showError(`Selected media type not supported by all chosen platforms`);
                return false;
            }
            
            // If media type is selected (not text), ensure files are uploaded
            if (this.mediaType !== 'text' && this.uploadedFiles.length === 0) {
                this.showError(`Please upload at least one ${this.mediaType} file`);
                return false;
            }
            
            return true;
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
            // Validate all steps before submission
            let isValid = true;
            for (let i = 0; i < this.steps.length; i++) {
                this.currentStep = i;
                if (!this.validateCurrentStep()) {
                    isValid = false;
                    break;
                }
            }
            
            if (isValid) {
                // Get the parent form (the main form that contains this multi-step form)
                const form = this.$el.closest('form');
                if (!form) {
                    this.showError('Form not found');
                    return;
                }
                
                // Create FormData for proper file upload handling
                const formData = new FormData(form);
                
                // Add selected accounts
                this.selectedAccounts.forEach(accountId => {
                    formData.append('selected_accounts[]', accountId);
                });
                
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
