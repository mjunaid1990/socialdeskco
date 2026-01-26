@extends('backend.layouts.app')

@section('title', 'Create Post')

@section('content')
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create New Post</h1>
        <p class="mt-1 text-sm text-gray-600">Schedule and publish content across your social media accounts</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data" x-data="multiStepForm()" @submit.prevent="submitForm">
            @csrf
            @include('backend.posts._multi_step_form')
            
            <div class="mt-6 flex justify-start space-x-3">
                <a href="{{ route('admin.posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>


@endsection

@push('scripts')
    @include('backend.posts._scripts')
@endpush