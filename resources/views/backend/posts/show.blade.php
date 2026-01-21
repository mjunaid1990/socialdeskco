@extends('backend.layouts.app')

@section('title', 'View Post')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Post Details</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $post->title }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.posts.edit', $post->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                Edit Post
            </a>
            <a href="{{ route('admin.posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                Back to Posts
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 pb-6 border-b">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Title</h3>
                <p class="mt-1 text-lg font-medium text-gray-900">{{ $post->title }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Status</h3>
                <p class="mt-1">
                    @if($post->status === 'draft')
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Draft
                        </span>
                    @elseif($post->status === 'scheduled')
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            Scheduled
                        </span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Published
                        </span>
                    @endif
                </p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Scheduled Date</h3>
                <p class="mt-1 text-gray-900">
                    {{ $post->scheduled_at ? $post->scheduled_at->format('M j, Y g:i A') : '-' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Content</h3>
                <div class="mt-2 text-gray-700">
                    <p>{!! nl2br(e($post->content)) !!}</p>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Social Account</h3>
                <div class="mt-2">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 mr-3">
                            @if($post->socialAccount->platform === 'facebook')
                                <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12z"></path>
                                </svg>
                            @elseif($post->socialAccount->platform === 'instagram')
                                <svg class="w-8 h-8 text-pink-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z"></path>
                                </svg>
                            @elseif($post->socialAccount->platform === 'linkedin')
                                <svg class="w-8 h-8 text-blue-700" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"></path>
                                </svg>
                            @else
                                <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $post->socialAccount->account_name }}</p>
                            <p class="text-sm text-gray-500">{{ ucfirst($post->socialAccount->platform) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 pb-6 border-b">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Likes</h3>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($post->likes_count) }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Comments</h3>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($post->comments_count) }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Shares</h3>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($post->shares_count) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Created</h3>
                <p class="mt-1 text-gray-900">{{ $post->created_at->format('M j, Y g:i A') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Last Updated</h3>
                <p class="mt-1 text-gray-900">{{ $post->updated_at->format('M j, Y g:i A') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection