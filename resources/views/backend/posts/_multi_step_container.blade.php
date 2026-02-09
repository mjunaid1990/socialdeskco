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
        @include('backend.posts._steps.step1_accounts')
        @include('backend.posts._steps.step2_media_type')
        @include('backend.posts._steps.step3_basic_info')
        @include('backend.posts._steps.step4_platform_fields')

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