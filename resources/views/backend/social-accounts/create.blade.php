@extends('backend.layouts.app')

@section('title', 'Connect Social Account')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Connect New Social Account</h1>
        <p class="mt-1 text-sm text-gray-600">Click on any platform below to connect your account securely via OAuth</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(\App\Helpers\PlatformHelper::getPlatforms() as $key => $label)
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="text-center">
                        <div class="mb-4">
                            @switch($key)
                                @case('facebook')
                                    <svg class="w-12 h-12 mx-auto text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    @break
                                @case('instagram')
                                    <svg class="w-12 h-12 mx-auto text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1 1 12.324 0 6.162 6.162 0 0 1-12.324 0zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm4.965-10.405a1.44 1.44 0 1 1 2.881.001 1.44 1.44 0 0 1-2.881-.001z"/>
                                    </svg>
                                    @break
                                @case('twitter')
                                    <svg class="w-12 h-12 mx-auto text-black" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                    </svg>
                                    @break
                                @case('linkedin')
                                    <svg class="w-12 h-12 mx-auto text-blue-700" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                    @break
                                @default
                                    <div class="w-12 h-12 mx-auto bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-gray-600 font-bold text-lg">{{ strtoupper(substr($label, 0, 2)) }}</span>
                                    </div>
                            @endswitch
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $label }}</h3>
                        <p class="text-sm text-gray-600 mb-4">Connect your {{ $label }} account to enable automation</p>
                        
                        <a href="#" 
                           onclick="openOAuthPopup('{{ route('oauth.redirect', $key) }}', '{{ $label }}')"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Connect {{ $label }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-8 text-center">
            <a href="{{ route('admin.social-accounts.index') }}" class="text-gray-600 hover:text-gray-800">
                ← Back to Connected Accounts
            </a>
        </div>
    </div>
</div>

<script>
function openOAuthPopup(url, platform) {
    console.log('Opening OAuth popup for:', platform);
    console.log('OAuth URL:', url);
    
    const width = 600;
    const height = 700;
    const left = (screen.width / 2) - (width / 2);
    const top = (screen.height / 2) - (height / 2);
    
    const popup = window.open(
        url,
        'oauth_' + platform,
        `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
    );
    
    if (!popup) {
        console.error('Popup blocked! Please allow popups for this site.');
        alert('Popup blocked! Please allow popups for this site and try again.');
        return;
    }
    
    console.log('Popup opened successfully');
    
    // Poll for popup closure
    const checkClosed = setInterval(() => {
        console.log('Checking if popup is closed...');
        if (popup.closed) {
            console.log('Popup closed, redirecting to social accounts page');
            clearInterval(checkClosed);
            // Reload the page to show updated social accounts
            window.location.href = '{{ route("admin.social-accounts.index") }}';
        }
    }, 1000);
}
</script>

@endsection